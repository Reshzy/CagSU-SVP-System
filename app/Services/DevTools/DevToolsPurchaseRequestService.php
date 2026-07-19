<?php

namespace App\Services\DevTools;

use App\Models\DepartmentBudget;
use App\Models\Document;
use App\Models\PpmpItem;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestItem;
use App\Models\User;
use App\Models\WorkflowApproval;
use App\Notifications\PurchaseRequestSubmitted;
use App\Services\BacResolutionService;
use App\Services\PpmpQuarterlyTracker;
use App\Services\PurchaseRequestActivityLogger;
use App\Services\WorkflowRouter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

    /**
     * Statuses that require BAC procurement method + resolution side effects.
     *
     * @var list<string>
     */
    public const BAC_READY_STATUSES = [
        'bac_evaluation',
        'bac_approved',
        'partial_po_generation',
        'po_generation',
        'po_approved',
        'supplier_processing',
        'delivered',
        'completed',
    ];

    public function __construct(
        protected PurchaseRequestActivityLogger $activityLogger,
        protected PpmpQuarterlyTracker $quarterlyTracker,
        protected BacResolutionService $bacResolutionService,
    ) {}

    /**
     * @param  array{
     *     department_id: int,
     *     requester_id: int,
     *     purpose: string,
     *     justification: string,
     *     date_needed?: string|null,
     *     status?: string,
     *     items: list<array<string, mixed>>,
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

            $purchaseRequest = $purchaseRequest->fresh(['items', 'requester', 'department']);

            if ($this->statusRequiresBacSetup($status)) {
                $this->prepareForBacEvaluation($purchaseRequest, $admin);
                $purchaseRequest = $purchaseRequest->fresh(['items', 'requester', 'department']);
            }

            return $purchaseRequest;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    public function calculateTotalCost(array $items): float
    {
        $total = 0.0;

        foreach ($items as $item) {
            // Lot children are rolled into the lot header total.
            if ($this->isLotChildPayload($item)) {
                continue;
            }

            $isLot = ! empty($item['is_lot']);
            $quantity = $isLot ? 1.0 : (float) ($item['quantity_requested'] ?? 0);
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
            if ($this->statusRequiresBacSetup($status)) {
                $this->prepareForBacEvaluation($purchaseRequest, $admin);
            }

            return $purchaseRequest->fresh();
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

        if ($this->statusRequiresBacSetup($status)) {
            $this->prepareForBacEvaluation($purchaseRequest->fresh(), $admin);
        }

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
     * Mirror CEO approval side effects so BAC manage/RFQ pages do not 500.
     */
    public function prepareForBacEvaluation(PurchaseRequest $purchaseRequest, User $admin): void
    {
        $needsMethod = empty($purchaseRequest->procurement_method);
        $needsResolutionNumber = empty($purchaseRequest->resolution_number);
        $hasResolutionDocument = Document::query()
            ->where('documentable_type', PurchaseRequest::class)
            ->where('documentable_id', $purchaseRequest->id)
            ->where('document_type', 'bac_resolution')
            ->exists();

        if ($needsMethod) {
            $purchaseRequest->procurement_method = 'small_value_procurement';
            $purchaseRequest->procurement_method_set_at = now();
            $purchaseRequest->procurement_method_set_by = $admin->id;
        }

        if ($needsResolutionNumber) {
            $purchaseRequest->resolution_number = PurchaseRequest::generateNextResolutionNumber();
        }

        if ($needsMethod || $needsResolutionNumber) {
            $purchaseRequest->save();
        }

        WorkflowApproval::updateOrCreate(
            [
                'purchase_request_id' => $purchaseRequest->id,
                'step_name' => 'ceo_initial_approval',
            ],
            [
                'step_order' => 2,
                'approver_id' => $admin->id,
                'approved_by' => $admin->id,
                'status' => 'approved',
                'comments' => 'Auto-approved via Dev Tools for BAC testing',
                'assigned_at' => now()->subDay(),
                'responded_at' => now(),
                'days_to_respond' => 1,
            ]
        );

        if (! $hasResolutionDocument) {
            try {
                $purchaseRequest->loadMissing(['requester', 'department']);
                $this->bacResolutionService->generateResolution($purchaseRequest, null);
                $this->activityLogger->logResolutionGenerated(
                    $purchaseRequest,
                    $purchaseRequest->resolution_number,
                    $admin->id
                );
            } catch (\Throwable $e) {
                Log::error('Dev Tools failed to generate BAC resolution for PR '.$purchaseRequest->pr_number.': '.$e->getMessage());
            }
        }

        try {
            WorkflowRouter::createPendingForRole($purchaseRequest, 'bac_evaluation', 'BAC Secretariat');
        } catch (\Throwable) {
            // BAC Secretariat role may be missing in sparse test DBs.
        }
    }

    public function statusRequiresBacSetup(string $status): bool
    {
        return in_array($status, self::BAC_READY_STATUSES, true);
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    protected function createItems(PurchaseRequest $purchaseRequest, array $items): void
    {
        $currentQuarter = $this->quarterlyTracker->getQuarterFromDate();

        /** @var array<int, PurchaseRequestItem> */
        $createdByIndex = [];

        foreach ($items as $index => $itemData) {
            $isLot = ! empty($itemData['is_lot']);
            $quantity = $isLot ? 1 : (int) $itemData['quantity_requested'];
            $estimatedTotal = (float) $itemData['estimated_unit_cost'] * $quantity;

            $prItemData = [
                'purchase_request_id' => $purchaseRequest->id,
                'ppmp_item_id' => $itemData['ppmp_item_id'] ?? null,
                'item_code' => $itemData['item_code'] ?? null,
                'item_name' => $itemData['item_name'] ?? null,
                'detailed_specifications' => $itemData['detailed_specifications'] ?? null,
                'unit_of_measure' => $isLot ? 'lot' : ($itemData['unit_of_measure'] ?? null),
                'quantity_requested' => $quantity,
                'estimated_unit_cost' => $itemData['estimated_unit_cost'],
                'estimated_total_cost' => $estimatedTotal,
                'ppmp_quarter' => $currentQuarter,
                'is_lot' => $isLot,
                'lot_name' => $isLot ? ($itemData['lot_name'] ?? null) : null,
                'parent_lot_id' => null,
                'item_category' => null,
            ];

            if (
                isset($itemData['parent_lot_index'])
                && $itemData['parent_lot_index'] !== ''
                && isset($createdByIndex[(int) $itemData['parent_lot_index']])
            ) {
                $prItemData['parent_lot_id'] = $createdByIndex[(int) $itemData['parent_lot_index']]->id;
            }

            if (! $isLot && ! empty($itemData['ppmp_item_id'])) {
                $ppmpItem = PpmpItem::with('appItem')->find($itemData['ppmp_item_id']);

                if ($ppmpItem?->appItem) {
                    $prItemData['item_category'] = $ppmpItem->appItem->category;
                    $prItemData['ppmp_planned_qty_for_quarter'] = $ppmpItem->getQuarterlyQuantity($currentQuarter);
                    $prItemData['ppmp_remaining_qty_at_creation'] = $ppmpItem->getRemainingQuantity($currentQuarter);
                }
            }

            $createdByIndex[$index] = PurchaseRequestItem::create($prItemData);
        }
    }

    /**
     * @param  array<string, mixed>  $item
     */
    protected function isLotChildPayload(array $item): bool
    {
        return isset($item['parent_lot_index'])
            && $item['parent_lot_index'] !== ''
            && $item['parent_lot_index'] !== null;
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
