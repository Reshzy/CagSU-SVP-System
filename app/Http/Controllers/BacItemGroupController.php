<?php

namespace App\Http\Controllers;

use App\Models\PrItemGroup;
use App\Models\PurchaseRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BacItemGroupController extends Controller
{
    /**
     * Show the grouping form for splitting items into groups
     */
    public function create(PurchaseRequest $purchaseRequest): View
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403, 'PR must be in BAC evaluation stage to split items.');

        $purchaseRequest->load('items', 'itemGroups.items');

        return view('bac.item-groups.create', compact('purchaseRequest'));
    }

    /**
     * Store the item groups
     */
    public function store(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403, 'PR must be in BAC evaluation stage to split items.');

        $validated = $request->validate([
            'groups' => ['required', 'array', 'min:1'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.items' => ['required', 'array', 'min:1'],
            'groups.*.items.*' => ['required', 'exists:purchase_request_items,id'],
        ]);

        DB::transaction(function () use ($validated, $purchaseRequest) {
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

                // Assign items to this group
                foreach ($groupData['items'] as $itemId) {
                    DB::table('purchase_request_items')
                        ->where('id', $itemId)
                        ->update(['pr_item_group_id' => $group->id]);
                }
            }
        });

        return redirect()
            ->route('bac.quotations.manage', $purchaseRequest)
            ->with('status', 'Items have been successfully grouped.');
    }

    /**
     * Show the edit form for existing groups
     */
    public function edit(PurchaseRequest $purchaseRequest): View
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403, 'PR must be in BAC evaluation stage to edit groups.');

        $purchaseRequest->load('items', 'itemGroups.items');

        return view('bac.item-groups.edit', compact('purchaseRequest'));
    }

    /**
     * Update existing groups
     */
    public function update(Request $request, PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403, 'PR must be in BAC evaluation stage to edit groups.');

        $validated = $request->validate([
            'groups' => ['required', 'array', 'min:1'],
            'groups.*.name' => ['required', 'string', 'max:255'],
            'groups.*.items' => ['required', 'array', 'min:1'],
            'groups.*.items.*' => ['required', 'exists:purchase_request_items,id'],
        ]);

        DB::transaction(function () use ($validated, $purchaseRequest) {
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

                // Assign items to this group
                foreach ($groupData['items'] as $itemId) {
                    DB::table('purchase_request_items')
                        ->where('id', $itemId)
                        ->update(['pr_item_group_id' => $group->id]);
                }
            }
        });

        return redirect()
            ->route('bac.quotations.manage', $purchaseRequest)
            ->with('status', 'Item groups have been updated.');
    }

    /**
     * Delete all groups and ungroup items
     */
    public function destroy(PurchaseRequest $purchaseRequest): RedirectResponse
    {
        abort_unless($purchaseRequest->status === 'bac_evaluation', 403, 'PR must be in BAC evaluation stage.');

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
}
