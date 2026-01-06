<?php

namespace App\Observers;

use App\Models\PurchaseRequest;
use App\Services\PpmpQuarterlyTracker;
use App\Services\PurchaseRequestActivityLogger;
use Illuminate\Support\Facades\Log;

class PurchaseRequestObserver
{
    protected PurchaseRequestActivityLogger $activityLogger;

    public function __construct(PurchaseRequestActivityLogger $activityLogger)
    {
        $this->activityLogger = $activityLogger;
    }

    /**
     * Handle the PurchaseRequest "creating" event.
     * Set pr_quarter before creating the PR
     */
    public function creating(PurchaseRequest $purchaseRequest): void
    {
        // Automatically set pr_quarter if not already set
        if ($purchaseRequest->pr_quarter === null) {
            $quarterlyTracker = app(PpmpQuarterlyTracker::class);
            $purchaseRequest->pr_quarter = $quarterlyTracker->getQuarterFromDate();
        }
    }

    /**
     * Handle the PurchaseRequest "created" event.
     * Reserve budget when PR is created
     */
    public function created(PurchaseRequest $purchaseRequest): void
    {
        // Log creation activity
        try {
            $this->activityLogger->logCreation($purchaseRequest, $purchaseRequest->requester_id);
        } catch (\Exception $e) {
            Log::error('Failed to log PR creation activity: '.$purchaseRequest->pr_number, [
                'error' => $e->getMessage(),
            ]);
        }

        // Only reserve budget if PR is submitted (not draft)
        if ($purchaseRequest->status !== 'draft') {
            try {
                $purchaseRequest->reserveDepartmentBudget();

                // Log submission if created in submitted state
                if ($purchaseRequest->status === 'supply_office_review' || $purchaseRequest->status === 'submitted') {
                    $this->activityLogger->logSubmission($purchaseRequest, $purchaseRequest->requester_id);
                }
            } catch (\Exception $e) {
                Log::error('Failed to reserve budget for PR: '.$purchaseRequest->pr_number, [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Handle the PurchaseRequest "updated" event.
     * Manage budget based on status changes and log activities
     */
    public function updated(PurchaseRequest $purchaseRequest): void
    {
        // Check if status changed
        if ($purchaseRequest->isDirty('status')) {
            $oldStatus = $purchaseRequest->getOriginal('status');
            $newStatus = $purchaseRequest->status;

            // Log status change activity
            try {
                $this->activityLogger->logStatusChange($purchaseRequest, $oldStatus, $newStatus);

                // Log specific action types
                if ($newStatus === 'returned_by_supply' && $purchaseRequest->return_remarks) {
                    $this->activityLogger->logReturn(
                        $purchaseRequest,
                        $purchaseRequest->return_remarks,
                        $purchaseRequest->returned_by
                    );
                }

                if ($newStatus === 'rejected' && $purchaseRequest->rejection_reason) {
                    $this->activityLogger->logRejection(
                        $purchaseRequest,
                        $purchaseRequest->rejection_reason,
                        $purchaseRequest->rejected_by
                    );
                }
            } catch (\Exception $e) {
                Log::error('Failed to log PR status change activity: '.$purchaseRequest->pr_number, [
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                // If moving from draft to submitted, reserve budget
                if ($oldStatus === 'draft' && $newStatus === 'submitted') {
                    $purchaseRequest->reserveDepartmentBudget();
                }

                // If PR is completed, move from reserved to utilized
                if ($newStatus === 'completed') {
                    $purchaseRequest->utilizeDepartmentBudget();
                }

                // If PR is cancelled, rejected, or returned, release reserved budget
                // Note: PPMP quantities are automatically released via dynamic calculation in PpmpItem::getRemainingQuantity()
                if (in_array($newStatus, ['cancelled', 'rejected', 'returned_by_supply'])) {
                    // Only release if it was previously in a non-draft state
                    if ($oldStatus !== 'draft') {
                        $purchaseRequest->releaseReservedBudget();
                    }
                }
            } catch (\Exception $e) {
                Log::error('Failed to update budget for PR: '.$purchaseRequest->pr_number, [
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log handler assignment changes
        if ($purchaseRequest->isDirty('current_handler_id') && $purchaseRequest->current_handler_id) {
            try {
                $this->activityLogger->logAssignment($purchaseRequest, $purchaseRequest->current_handler_id);
            } catch (\Exception $e) {
                Log::error('Failed to log PR assignment activity: '.$purchaseRequest->pr_number, [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Log notes addition
        if ($purchaseRequest->isDirty('current_step_notes') && $purchaseRequest->current_step_notes) {
            try {
                $this->activityLogger->logNotesAdded($purchaseRequest, $purchaseRequest->current_step_notes);
            } catch (\Exception $e) {
                Log::error('Failed to log PR notes activity: '.$purchaseRequest->pr_number, [
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
        if (! in_array($purchaseRequest->status, ['draft', 'completed', 'cancelled', 'rejected'])) {
            try {
                $purchaseRequest->releaseReservedBudget();
            } catch (\Exception $e) {
                Log::error('Failed to release budget for deleted PR: '.$purchaseRequest->pr_number, [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
