<?php

namespace App\Services\DevTools;

use App\Models\DepartmentBudget;
use App\Models\PpmpItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Notifications\PurchaseRequestSubmitted;
use App\Services\PpmpQuarterlyTracker;
use App\Services\PurchaseRequestActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DevToolsPurchaseRequestService
{
    /**
     * @var list<string>
     */
    public const STATUSES = [
        'draft',
        'submitted',
        'supply_office_review',
        'budget_office_review',
        'ceo_approval',
        'bac_evaluation',
        'bac_approved',
        'partial_po_generation',
        'po_generation',
        'po_approved',
        'supplier_processing',
        'delivered',
        'completed',
        'cancelled',
        'rejected',
        'returned_by_supply',
    ];

    /**
     * @var array<string, string>
     */
    public const JUMP_PRESETS = [
        'supply' => 'supply_office_review',
        'budget' => 'budget_office_review',
        'ceo' => 'ceo_approval',
        'bac' => 'bac_evaluation',
        'completed' => 'completed',
    ];

    public function __construct(
        protected PurchaseRequestActivityLogger $activityLogger,
        protected PpmpQuarterlyTracker $quarterlyTracker,
    ) {}

    /**
     * @param  array{
     *     department_id: int,
     *     requester_id: int,
     *     purpose: string,
     *     justification: string,
     *     date_needed?: string|null,
     *     status?: string,
     *     items: list<array{
     *         ppmp_item_id?: int|null,
     *         item_code?: string|null,
     *         item_name: string,
     *         detailed_specifications?: string|null,
     *         unit_of_measure: string,
     *         quantity_requested: int|float,
     *         estimated_unit_cost: float|int|string
     *     }>,
     *     notify_officers?: bool
     * }  $data
     */
    public function createPurchaseRequest(array $data, User $admin): PurchaseRequest
    {
        $status = $data['status'] ?? 'supply_office_review';

        if (! in_array($status, self::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid purchase request status.',
            ]);
        }

        $items = $data['items'] ?? [];

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'At least one item is required.',
            ]);
        }

        $totalCost = $this->calculateTotalCost($items);
        $fiscalYear = (int) date('Y');
        $budget = DepartmentBudget::getOrCreateForDepartment((int) $data['department_id'], $fiscalYear);

        if ($status !== 'draft' && ! $budget->canReserve($totalCost)) {
            throw ValidationException::withMessages([
                'budget' => 'Insufficient budget. Available: ₱'.number_format($budget->getAvailableBudget(), 2)
                    .', Required: ₱'.number_format($totalCost, 2),
            ]);
        }

        return DB::transaction(function () use ($data, $admin, $status, $items, $totalCost) {
            $purchaseRequest = PurchaseRequest::create([
                'pr_number' => PurchaseRequest::generateNextPrNumber(),
                'requester_id' => $data['requester_id'],
                'department_id' => $data['department_id'],
                'purpose' => $data['purpose'],
                'justification' => $data['justification'] ?? null,
                'date_needed' => $data['date_needed'] ?? null,
                'estimated_total' => $totalCost,
                'funding_source' => null,
                'budget_code' => null,
                'procurement_type' => null,
                'procurement_method' => null,
                'status' => $status,
                'submitted_at' => $status === 'draft' ? null : now(),
                'status_updated_at' => now(),
                'current_handler_id' => null,
                'has_ppmp' => collect($items)->contains(fn (array $item): bool => ! empty($item['ppmp_item_id'])),
            ]);

            $this->createItems($purchaseRequest, $items);

            // The model observer runs on create before items exist, so it reserves ₱0.
            // Reserve the real total now that line items are attached.
            if ($status !== 'draft') {
                $purchaseRequest->load('items');
                $purchaseRequest->reserveDepartmentBudget();
            }

            $this->activityLogger->log($purchaseRequest, [
                'action' => 'created',
                'description' => 'Created via Dev Tools by '.$admin->name,
                'user_id' => $admin->id,
                'new_value' => [
                    'via' => 'dev_tools',
                    'admin_id' => $admin->id,
                    'requester_id' => $data['requester_id'],
                    'department_id' => $data['department_id'],
                ],
            ]);

            if (! empty($data['notify_officers'])) {
                $this->notifySupplyOffice($purchaseRequest);
            }

            return $purchaseRequest->fresh(['items', 'requester', 'department']);
        });
    }

    /**
     * @param  list<array{
     *     ppmp_item_id?: int|null,
     *     item_code?: string|null,
     *     item_name: string,
     *     detailed_specifications?: string|null,
     *     unit_of_measure: string,
     *     quantity_requested: int|float,
     *     estimated_unit_cost: float|int|string
     * }>  $items
     */
    public function calculateTotalCost(array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            $quantity = (float) ($item['quantity_requested'] ?? 0);
            $unitCost = (float) ($item['estimated_unit_cost'] ?? 0);
            $total += $quantity * $unitCost;
        }

        return round($total, 2);
    }

    public function updateStatus(PurchaseRequest $purchaseRequest, string $status, User $admin): PurchaseRequest
    {
        if (! in_array($status, self::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Invalid purchase request status.',
            ]);
        }

        $oldStatus = $purchaseRequest->status;

        if ($oldStatus === $status) {
            return $purchaseRequest;
        }

        $purchaseRequest->status = $status;
        $purchaseRequest->status_updated_at = now();

        if ($status === 'completed' && $purchaseRequest->completed_at === null) {
            $purchaseRequest->completed_at = now();
        }

        if (in_array($status, ['submitted', 'supply_office_review'], true) && $purchaseRequest->submitted_at === null) {
            $purchaseRequest->submitted_at = now();
        }

        $purchaseRequest->save();

        $this->activityLogger->log($purchaseRequest, [
            'action' => 'status_changed',
            'old_value' => ['status' => $oldStatus],
            'new_value' => [
                'status' => $status,
                'via' => 'dev_tools',
                'admin_id' => $admin->id,
            ],
            'description' => 'Dev Tools override by '.$admin->name.': '.$oldStatus.' → '.$status,
            'user_id' => $admin->id,
        ]);

        return $purchaseRequest->fresh();
    }

    public function jumpToPreset(PurchaseRequest $purchaseRequest, string $preset, User $admin): PurchaseRequest
    {
        if (! isset(self::JUMP_PRESETS[$preset])) {
            throw ValidationException::withMessages([
                'preset' => 'Unknown workflow jump preset.',
            ]);
        }

        return $this->updateStatus($purchaseRequest, self::JUMP_PRESETS[$preset], $admin);
    }

    /**
     * @param  list<array{
     *     ppmp_item_id?: int|null,
     *     item_code?: string|null,
     *     item_name: string,
     *     detailed_specifications?: string|null,
     *     unit_of_measure: string,
     *     quantity_requested: int|float,
     *     estimated_unit_cost: float|int|string
     * }>  $items
     */
    protected function createItems(PurchaseRequest $purchaseRequest, array $items): void
    {
        $currentQuarter = $this->quarterlyTracker->getQuarterFromDate();

        foreach ($items as $itemData) {
            $quantity = (int) $itemData['quantity_requested'];
            $estimatedTotal = (float) $itemData['estimated_unit_cost'] * $quantity;

            $prItemData = [
                'purchase_request_id' => $purchaseRequest->id,
                'ppmp_item_id' => $itemData['ppmp_item_id'] ?? null,
                'item_code' => $itemData['item_code'] ?? null,
                'item_name' => $itemData['item_name'] ?? null,
                'detailed_specifications' => $itemData['detailed_specifications'] ?? null,
                'unit_of_measure' => $itemData['unit_of_measure'] ?? null,
                'quantity_requested' => $quantity,
                'estimated_unit_cost' => $itemData['estimated_unit_cost'],
                'estimated_total_cost' => $estimatedTotal,
                'ppmp_quarter' => $currentQuarter,
                'is_lot' => false,
                'item_category' => null,
            ];

            if (! empty($itemData['ppmp_item_id'])) {
                $ppmpItem = PpmpItem::with('appItem')->find($itemData['ppmp_item_id']);

                if ($ppmpItem?->appItem) {
                    $prItemData['item_category'] = $ppmpItem->appItem->category;
                    $prItemData['ppmp_planned_qty_for_quarter'] = $ppmpItem->getQuarterlyQuantity($currentQuarter);
                    $prItemData['ppmp_remaining_qty_at_creation'] = $ppmpItem->getRemainingQuantity($currentQuarter);
                }
            }

            PurchaseRequestItem::create($prItemData);
        }
    }

    protected function notifySupplyOffice(PurchaseRequest $purchaseRequest): void
    {
        try {
            $supplyUsers = User::role('Supply Officer')->get();

            foreach ($supplyUsers as $user) {
                $user->notify(new PurchaseRequestSubmitted($purchaseRequest));
            }
        } catch (\Throwable) {
            // Roles or mail may not be configured in local testing.
        }
    }
}
