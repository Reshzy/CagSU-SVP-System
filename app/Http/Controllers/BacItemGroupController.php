<?php

namespace App\Http\Controllers;

use App\Models\PrItemGroup;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Services\PurchaseRequestActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BacItemGroupController extends Controller
{
    /**
     * Show the grouping form for splitting items into groups
     */
    public function create(PurchaseRequest $purchaseRequest): View
    {
        abort_unless($purchaseRequest->canManageGroups(), 403, 'PR must be in BAC evaluation or partial PO generation stage to split items.');

        $purchaseRequest->load('items.lotChildren', 'itemGroups.items');

        $quotableItems = $this->quotableItems($purchaseRequest);
        $quotableItemsPayload = $this->quotableItemsPayload($quotableItems);

        return view('bac.item-groups.create', compact('purchaseRequest', 'quotableItems', 'quotableItemsPayload'));
    }

    /**
     * Store the item groups
     */
    public function store(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->canManageGroups(), 403, 'PR must be in BAC evaluation or partial PO generation stage to split items.');

        $validated = $request->validate([
            'groups' => ['required', 'array', 'min:1'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.items' => ['required', 'array', 'min:1'],
            'groups.*.items.*' => ['required', 'exists:purchase_request_items,id'],
        ]);

        $groupsInfo = [];

        DB::transaction(function () use ($validated, $purchaseRequest, &$groupsInfo) {
            // Delete existing groups if any
            $purchaseRequest->itemGroups()->delete();

            // Create new groups
            foreach ($validated['groups'] as $index => $groupData) {
                $groupCode = PrItemGroup::generateNextGroupCode($purchaseRequest);

                $group = PrItemGroup::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'group_name' => $groupData['name'],
                    'group_code' => $groupCode,
                    'display_order' => $index + 1,
                ]);

                $assignedCount = $this->assignItemsToGroup($purchaseRequest, $group, $groupData['items']);

                $groupsInfo[] = [
                    'group_code' => $groupCode,
                    'group_name' => $groupData['name'],
                    'item_count' => $assignedCount,
                ];
            }
        });

        // Log activity for item groups creation
        $activityLogger = new PurchaseRequestActivityLogger;
        $activityLogger->logItemGroupsCreated($purchaseRequest, $groupsInfo);

        return redirect()
            ->route('bac.quotations.manage', $purchaseRequest)
            ->with('status', 'Items have been successfully grouped.');
    }

    /**
     * Show the edit form for existing groups
     */
    public function edit(PurchaseRequest $purchaseRequest): View
    {
        abort_unless($purchaseRequest->canManageGroups(), 403, 'PR must be in BAC evaluation or partial PO generation stage to edit groups.');

        $purchaseRequest->load('items.lotChildren', 'itemGroups.items');

        $quotableItems = $this->quotableItems($purchaseRequest);
        $quotableItemsPayload = $this->quotableItemsPayload($quotableItems);

        return view('bac.item-groups.edit', compact('purchaseRequest', 'quotableItems', 'quotableItemsPayload'));
    }

    /**
     * Update existing groups
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->canManageGroups(), 403, 'PR must be in BAC evaluation or partial PO generation stage to edit groups.');

        $validated = $request->validate([
            'groups' => ['required', 'array', 'min:1'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.items' => ['required', 'array', 'min:1'],
            'groups.*.items.*' => ['required', 'exists:purchase_request_items,id'],
        ]);

        $groupsInfo = [];

        DB::transaction(function () use ($validated, $purchaseRequest, &$groupsInfo) {
            // Clear all item group assignments
            DB::table('purchase_request_items')
                ->where('purchase_request_id', $purchaseRequest->id)
                ->update(['pr_item_group_id' => null]);

            // Delete existing groups
            $purchaseRequest->itemGroups()->delete();

            // Create new groups
            foreach ($validated['groups'] as $index => $groupData) {
                $groupCode = PrItemGroup::generateNextGroupCode($purchaseRequest);

                $group = PrItemGroup::create([
                    'purchase_request_id' => $purchaseRequest->id,
                    'group_name' => $groupData['name'],
                    'group_code' => $groupCode,
                    'display_order' => $index + 1,
                ]);

                $assignedCount = $this->assignItemsToGroup($purchaseRequest, $group, $groupData['items']);

                $groupsInfo[] = [
                    'group_code' => $groupCode,
                    'group_name' => $groupData['name'],
                    'item_count' => $assignedCount,
                ];
            }
        });

        // Log activity for item groups update
        $activityLogger = new PurchaseRequestActivityLogger;
        $activityLogger->logItemGroupsUpdated($purchaseRequest, $groupsInfo);

        return redirect()
            ->route('bac.quotations.manage', $purchaseRequest)
            ->with('status', 'Item groups have been updated.');
    }

    /**
     * Delete all groups and ungroup items
     */
    public function destroy(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->canManageGroups(), 403, 'PR must be in BAC evaluation or partial PO generation stage.');

        DB::transaction(function () use ($purchaseRequest) {
            // Clear all item group assignments
            DB::table('purchase_request_items')
                ->where('purchase_request_id', $purchaseRequest->id)
                ->update(['pr_item_group_id' => null]);

            // Delete all groups
            $purchaseRequest->itemGroups()->delete();
        });

        return redirect()
            ->route('bac.quotations.manage', $purchaseRequest)
            ->with('status', 'All groups have been removed.');
    }

    /**
     * Bid-grain items for grouping UI: lot headers and standalones only.
     *
     * @return Collection<int, PurchaseRequestItem>
     */
    private function quotableItems(PurchaseRequest $purchaseRequest): Collection
    {
        return $purchaseRequest->items
            ->filter(fn (PurchaseRequestItem $item) => ! $item->isLotChild())
            ->sortBy('id')
            ->values();
    }

    /**
     * JSON-friendly payload for dynamic "Add Another Group" checkboxes.
     *
     * @param  Collection<int, PurchaseRequestItem>  $quotableItems
     * @return list<array<string, mixed>>
     */
    private function quotableItemsPayload(Collection $quotableItems): array
    {
        return $quotableItems->map(function (PurchaseRequestItem $item): array {
            return [
                'id' => $item->id,
                'item_name' => $item->isLotHeader()
                    ? ($item->lot_name ?? $item->item_name)
                    : $item->item_name,
                'quantity_requested' => $item->quantity_requested,
                'unit_of_measure' => $item->unit_of_measure,
                'estimated_total_cost' => (float) $item->estimated_total_cost,
                'is_lot' => $item->isLotHeader(),
                'lot_children' => $item->isLotHeader()
                    ? $item->lotChildren->sortBy('id')->values()->map(fn (PurchaseRequestItem $child): array => [
                        'id' => $child->id,
                        'item_name' => $child->item_name,
                        'quantity_requested' => $child->quantity_requested,
                        'unit_of_measure' => $child->unit_of_measure,
                        'estimated_unit_cost' => (float) $child->estimated_unit_cost,
                    ])->all()
                    : [],
            ];
        })->values()->all();
    }

    /**
     * Assign submitted quotable item IDs to a group, cascading lot children.
     *
     * @param  array<int, int|string>  $itemIds
     */
    private function assignItemsToGroup(PurchaseRequest $purchaseRequest, PrItemGroup $group, array $itemIds): int
    {
        $itemsById = PurchaseRequestItem::query()
            ->with('lotChildren')
            ->where('purchase_request_id', $purchaseRequest->id)
            ->whereIn('id', $itemIds)
            ->get()
            ->keyBy('id');

        $assignedIds = [];

        foreach ($itemIds as $itemId) {
            $item = $itemsById->get((int) $itemId);

            if (! $item || $item->isLotChild()) {
                continue;
            }

            $assignedIds[] = $item->id;

            if ($item->isLotHeader()) {
                foreach ($item->lotChildren as $child) {
                    $assignedIds[] = $child->id;
                }
            }
        }

        $assignedIds = array_values(array_unique($assignedIds));

        if ($assignedIds !== []) {
            PurchaseRequestItem::query()
                ->whereIn('id', $assignedIds)
                ->update(['pr_item_group_id' => $group->id]);
        }

        return count($assignedIds);
    }
}
