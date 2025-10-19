<?php

namespace App\Observers;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Log;

class PurchaseRequestObserver
{
    /**
     * Handle the PurchaseRequest "created" event.
     * Reserve budget when PR is created
     */
    public function created(PurchaseRequest $purchaseRequest): void
    {
        // Only reserve budget if PR is submitted (not draft)
        if ($purchaseRequest->status !== 'draft') {
            try {
                $purchaseRequest->reserveDepartmentBudget();
            } catch (\Exception $e) {
                Log::error('Failed to reserve budget for PR: ' . $purchaseRequest->pr_number, [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the PurchaseRequest "updated" event.
     * Manage budget based on status changes
     */
    public function updated(PurchaseRequest $purchaseRequest): void
    {
        // Check if status changed
        if ($purchaseRequest->isDirty('status')) {
            $oldStatus = $purchaseRequest->getOriginal('status');
            $newStatus = $purchaseRequest->status;

            try {
                // If moving from draft to submitted, reserve budget
                if ($oldStatus === 'draft' && $newStatus === 'submitted') {
                    $purchaseRequest->reserveDepartmentBudget();
                }

                // If PR is completed, move from reserved to utilized
                if ($newStatus === 'completed') {
                    $purchaseRequest->utilizeDepartmentBudget();
                }

                // If PR is cancelled or rejected, release reserved budget
                if (in_array($newStatus, ['cancelled', 'rejected'])) {
                    // Only release if it was previously in a non-draft state
                    if ($oldStatus !== 'draft') {
                        $purchaseRequest->releaseReservedBudget();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to update budget for PR: ' . $purchaseRequest->pr_number, [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the PurchaseRequest "deleted" event.
     * Release reserved budget if PR is deleted
     */
    public function deleted(PurchaseRequest $purchaseRequest): void
    {
        // Release budget if PR was not draft or completed
        if (!in_array($purchaseRequest->status, ['draft', 'completed', 'cancelled', 'rejected'])) {
            try {
                $purchaseRequest->releaseReservedBudget();
            } catch (\Exception $e) {
                Log::error('Failed to release budget for deleted PR: ' . $purchaseRequest->pr_number, [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
