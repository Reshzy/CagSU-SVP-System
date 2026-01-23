<?php

namespace App\Services;

use App\Models\AoqGeneration;
use App\Models\PrItemGroup;
use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestActivity;
use App\Models\Quotation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class PurchaseRequestActivityLogger
{
    /**
     * Log a status change activity.
     */
    public function logStatusChange(
        PurchaseRequest $purchaseRequest,
        string $oldStatus,
        string $newStatus,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $description = $this->formatStatusChangeDescription($oldStatus, $newStatus);

        return $this->log($purchaseRequest, [
            'action' => 'status_changed',
            'old_value' => ['status' => $oldStatus],
            'new_value' => ['status' => $newStatus],
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log a PR return activity.
     */
    public function logReturn(
        PurchaseRequest $purchaseRequest,
        string $remarks,
        ?int $userId = null
    ): PurchaseRequestActivity {
        return $this->log($purchaseRequest, [
            'action' => 'returned',
            'new_value' => ['return_remarks' => $remarks],
            'description' => 'Purchase request returned to department with remarks',
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log a PR rejection activity.
     */
    public function logRejection(
        PurchaseRequest $purchaseRequest,
        string $reason,
        ?int $userId = null
    ): PurchaseRequestActivity {
        return $this->log($purchaseRequest, [
            'action' => 'rejected',
            'new_value' => ['rejection_reason' => $reason],
            'description' => 'Purchase request deferred',
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log a PR approval activity.
     */
    public function logApproval(
        PurchaseRequest $purchaseRequest,
        string $stage,
        ?int $userId = null
    ): PurchaseRequestActivity {
        return $this->log($purchaseRequest, [
            'action' => 'approved',
            'new_value' => ['stage' => $stage],
            'description' => "Purchase request approved at {$stage} stage",
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log PR creation activity.
     */
    public function logCreation(
        PurchaseRequest $purchaseRequest,
        ?int $userId = null
    ): PurchaseRequestActivity {
        return $this->log($purchaseRequest, [
            'action' => 'created',
            'description' => 'Purchase request created',
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log PR submission activity.
     */
    public function logSubmission(
        PurchaseRequest $purchaseRequest,
        ?int $userId = null
    ): PurchaseRequestActivity {
        return $this->log($purchaseRequest, [
            'action' => 'submitted',
            'description' => 'Purchase request submitted for review',
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log replacement PR creation activity.
     */
    public function logReplacementCreation(
        PurchaseRequest $newPurchaseRequest,
        PurchaseRequest $originalPurchaseRequest,
        ?int $userId = null
    ): PurchaseRequestActivity {
        // Log on the new PR
        $this->log($newPurchaseRequest, [
            'action' => 'replacement_created',
            'new_value' => ['replaces_pr_id' => $originalPurchaseRequest->id],
            'description' => "Created as replacement for {$originalPurchaseRequest->pr_number}",
            'user_id' => $userId ?? Auth::id(),
        ]);

        // Also log on the original PR
        return $this->log($originalPurchaseRequest, [
            'action' => 'replacement_created',
            'new_value' => ['replaced_by_pr_id' => $newPurchaseRequest->id],
            'description' => "Replaced by new PR {$newPurchaseRequest->pr_number}",
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log notes addition activity.
     */
    public function logNotesAdded(
        PurchaseRequest $purchaseRequest,
        string $notes,
        ?int $userId = null
    ): PurchaseRequestActivity {
        return $this->log($purchaseRequest, [
            'action' => 'notes_added',
            'new_value' => ['notes' => $notes],
            'description' => 'Notes added to purchase request',
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log handler assignment activity.
     */
    public function logAssignment(
        PurchaseRequest $purchaseRequest,
        int $handlerId,
        ?int $userId = null
    ): PurchaseRequestActivity {
        return $this->log($purchaseRequest, [
            'action' => 'assigned',
            'new_value' => ['current_handler_id' => $handlerId],
            'description' => 'Purchase request assigned to handler',
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log a generic activity.
     */
    public function log(PurchaseRequest $purchaseRequest, array $data): PurchaseRequestActivity
    {
        return PurchaseRequestActivity::create([
            'purchase_request_id' => $purchaseRequest->id,
            'user_id' => $data['user_id'] ?? null,
            'pr_item_group_id' => $data['pr_item_group_id'] ?? null,
            'action' => $data['action'],
            'old_value' => $data['old_value'] ?? null,
            'new_value' => $data['new_value'] ?? null,
            'description' => $data['description'],
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * Log BAC Resolution generation activity.
     */
    public function logResolutionGenerated(
        PurchaseRequest $purchaseRequest,
        ?string $resolutionNumber = null,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $description = 'BAC Resolution generated';
        if ($resolutionNumber) {
            $description .= " ({$resolutionNumber})";
        }

        return $this->log($purchaseRequest, [
            'action' => 'resolution_generated',
            'new_value' => [
                'resolution_number' => $resolutionNumber ?? $purchaseRequest->resolution_number,
                'procurement_method' => $purchaseRequest->procurement_method,
            ],
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log BAC Resolution regeneration activity.
     */
    public function logResolutionRegenerated(
        PurchaseRequest $purchaseRequest,
        ?string $resolutionNumber = null,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $description = 'BAC Resolution regenerated';
        if ($resolutionNumber) {
            $description .= " ({$resolutionNumber})";
        }

        return $this->log($purchaseRequest, [
            'action' => 'resolution_regenerated',
            'new_value' => [
                'resolution_number' => $resolutionNumber ?? $purchaseRequest->resolution_number,
            ],
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log RFQ generation activity.
     */
    public function logRfqGenerated(
        PurchaseRequest $purchaseRequest,
        ?PrItemGroup $group = null,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $description = 'Request for Quotation (RFQ) generated';
        $newValue = [];

        if ($group) {
            $description .= " for Group {$group->group_code} ({$group->group_name})";
            $newValue['group_id'] = $group->id;
            $newValue['group_code'] = $group->group_code;
            $newValue['group_name'] = $group->group_name;
        }

        return $this->log($purchaseRequest, [
            'action' => 'rfq_generated',
            'new_value' => $newValue,
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
            'pr_item_group_id' => $group?->id,
        ]);
    }

    /**
     * Log quotation submission activity.
     */
    public function logQuotationSubmitted(
        PurchaseRequest $purchaseRequest,
        Quotation $quotation,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $supplierName = $quotation->supplier?->business_name ?? 'Unknown Supplier';
        $group = $quotation->prItemGroup;

        $description = "Quotation submitted by {$supplierName}";
        if ($group) {
            $description .= " for Group {$group->group_code} ({$group->group_name})";
        }

        return $this->log($purchaseRequest, [
            'action' => 'quotation_submitted',
            'new_value' => [
                'quotation_id' => $quotation->id,
                'supplier_id' => $quotation->supplier_id,
                'supplier_name' => $supplierName,
                'total_amount' => $quotation->total_amount,
                'group_id' => $group?->id,
                'group_code' => $group?->group_code,
            ],
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
            'pr_item_group_id' => $group?->id,
        ]);
    }

    /**
     * Log quotation evaluation activity.
     */
    public function logQuotationEvaluated(
        PurchaseRequest $purchaseRequest,
        Quotation $quotation,
        string $evaluationStatus,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $supplierName = $quotation->supplier?->business_name ?? 'Unknown Supplier';
        $group = $quotation->prItemGroup;
        $statusLabel = $evaluationStatus === 'responsive' ? 'Responsive' : 'Non-Responsive';

        $description = "Quotation from {$supplierName} evaluated as {$statusLabel}";
        if ($group) {
            $description .= " for Group {$group->group_code}";
        }

        return $this->log($purchaseRequest, [
            'action' => 'quotation_evaluated',
            'new_value' => [
                'quotation_id' => $quotation->id,
                'supplier_id' => $quotation->supplier_id,
                'supplier_name' => $supplierName,
                'evaluation_status' => $evaluationStatus,
                'group_id' => $group?->id,
                'group_code' => $group?->group_code,
            ],
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
            'pr_item_group_id' => $group?->id,
        ]);
    }

    /**
     * Log AOQ generation activity.
     */
    public function logAoqGenerated(
        PurchaseRequest $purchaseRequest,
        AoqGeneration $aoqGeneration,
        ?PrItemGroup $group = null,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $description = 'Abstract of Quotation (AOQ) generated';
        if ($aoqGeneration->reference_number) {
            $description .= " ({$aoqGeneration->reference_number})";
        }

        if ($group) {
            $description .= " for Group {$group->group_code} ({$group->group_name})";
        }

        return $this->log($purchaseRequest, [
            'action' => 'aoq_generated',
            'new_value' => [
                'aoq_id' => $aoqGeneration->id,
                'reference_number' => $aoqGeneration->reference_number,
                'group_id' => $group?->id,
                'group_code' => $group?->group_code,
                'group_name' => $group?->group_name,
            ],
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
            'pr_item_group_id' => $group?->id,
        ]);
    }

    /**
     * Log tie resolution activity.
     *
     * @param  array<int, array{item_id: int, item_description: string, winner_supplier_id: int, winner_supplier_name: string}>  $resolvedItems
     */
    public function logTieResolved(
        PurchaseRequest $purchaseRequest,
        array $resolvedItems,
        ?PrItemGroup $group = null,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $itemCount = count($resolvedItems);
        $description = "Tie resolved for {$itemCount} item(s)";

        if ($group) {
            $description .= " in Group {$group->group_code}";
        }

        return $this->log($purchaseRequest, [
            'action' => 'tie_resolved',
            'new_value' => [
                'resolved_items' => $resolvedItems,
                'item_count' => $itemCount,
                'group_id' => $group?->id,
                'group_code' => $group?->group_code,
            ],
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
            'pr_item_group_id' => $group?->id,
        ]);
    }

    /**
     * Log BAC override activity.
     *
     * @param  array<int, array{item_id: int, item_description: string, original_winner: string, new_winner: string, reason: string}>  $overriddenItems
     */
    public function logBacOverride(
        PurchaseRequest $purchaseRequest,
        array $overriddenItems,
        ?PrItemGroup $group = null,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $itemCount = count($overriddenItems);
        $description = "BAC override applied to {$itemCount} item(s)";

        if ($group) {
            $description .= " in Group {$group->group_code}";
        }

        return $this->log($purchaseRequest, [
            'action' => 'bac_override',
            'new_value' => [
                'overridden_items' => $overriddenItems,
                'item_count' => $itemCount,
                'group_id' => $group?->id,
                'group_code' => $group?->group_code,
            ],
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
            'pr_item_group_id' => $group?->id,
        ]);
    }

    /**
     * Log supplier withdrawal activity.
     */
    public function logSupplierWithdrawal(
        PurchaseRequest $purchaseRequest,
        Quotation $quotation,
        ?string $reason = null,
        ?string $successorSupplierName = null,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $supplierName = $quotation->supplier?->business_name ?? 'Unknown Supplier';
        $group = $quotation->prItemGroup;

        $description = "{$supplierName} withdrew from bidding";
        if ($group) {
            $description .= " for Group {$group->group_code}";
        }
        if ($successorSupplierName) {
            $description .= ". Winner succession to {$successorSupplierName}";
        }

        return $this->log($purchaseRequest, [
            'action' => 'supplier_withdrawal',
            'new_value' => [
                'quotation_id' => $quotation->id,
                'supplier_id' => $quotation->supplier_id,
                'supplier_name' => $supplierName,
                'withdrawal_reason' => $reason,
                'successor_supplier' => $successorSupplierName,
                'group_id' => $group?->id,
                'group_code' => $group?->group_code,
            ],
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
            'pr_item_group_id' => $group?->id,
        ]);
    }

    /**
     * Log item groups creation activity.
     *
     * @param  array<int, array{group_code: string, group_name: string, item_count: int}>  $groups
     */
    public function logItemGroupsCreated(
        PurchaseRequest $purchaseRequest,
        array $groups,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $groupCount = count($groups);
        $groupCodes = array_column($groups, 'group_code');

        $description = "PR split into {$groupCount} groups (".implode(', ', $groupCodes).')';

        return $this->log($purchaseRequest, [
            'action' => 'item_groups_created',
            'new_value' => [
                'groups' => $groups,
                'group_count' => $groupCount,
            ],
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Log item groups update activity.
     *
     * @param  array<int, array{group_code: string, group_name: string, item_count: int}>  $groups
     */
    public function logItemGroupsUpdated(
        PurchaseRequest $purchaseRequest,
        array $groups,
        ?int $userId = null
    ): PurchaseRequestActivity {
        $groupCount = count($groups);
        $groupCodes = array_column($groups, 'group_code');

        $description = "Item groups updated ({$groupCount} groups: ".implode(', ', $groupCodes).')';

        return $this->log($purchaseRequest, [
            'action' => 'item_groups_updated',
            'new_value' => [
                'groups' => $groups,
                'group_count' => $groupCount,
            ],
            'description' => $description,
            'user_id' => $userId ?? Auth::id(),
        ]);
    }

    /**
     * Format a status change description.
     */
    protected function formatStatusChangeDescription(string $oldStatus, string $newStatus): string
    {
        $statusLabels = [
            'draft' => 'Draft',
            'submitted' => 'Submitted',
            'supply_office_review' => 'Supply Office Review',
            'budget_office_review' => 'Budget Office Review',
            'ceo_approval' => 'CEO Approval',
            'bac_evaluation' => 'BAC Evaluation',
            'bac_approved' => 'BAC Approved',
            'po_generation' => 'PO Generation',
            'po_approved' => 'PO Approved',
            'supplier_processing' => 'Supplier Processing',
            'delivered' => 'Delivered',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'rejected' => 'Deferred',
            'returned_by_supply' => 'Returned by Supply Office',
        ];

        $oldLabel = $statusLabels[$oldStatus] ?? ucwords(str_replace('_', ' ', $oldStatus));
        $newLabel = $statusLabels[$newStatus] ?? ucwords(str_replace('_', ' ', $newStatus));

        return "Status changed from {$oldLabel} to {$newLabel}";
    }
}
