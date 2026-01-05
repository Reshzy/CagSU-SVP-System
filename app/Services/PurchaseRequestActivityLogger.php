<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use App\Models\PurchaseRequestActivity;
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
            'description' => 'Purchase request rejected',
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
            'rejected' => 'Rejected',
            'returned_by_supply' => 'Returned by Supply Office',
        ];

        $oldLabel = $statusLabels[$oldStatus] ?? ucwords(str_replace('_', ' ', $oldStatus));
        $newLabel = $statusLabels[$newStatus] ?? ucwords(str_replace('_', ' ', $newStatus));

        return "Status changed from {$oldLabel} to {$newLabel}";
    }
}
