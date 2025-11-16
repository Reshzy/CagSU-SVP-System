<?php

namespace App\Services;

use App\Models\PurchaseRequest;
use App\Models\User;
use App\Models\WorkflowApproval;
use App\Notifications\PurchaseRequestActionRequired;

class WorkflowRouter
{
    /**
     * Map step names to a numeric order for tracking.
     * Updated workflow: PR Created → Budget Office → CEO → BAC → ... → Final
     */
    public static function getStepOrder(string $stepName): int
    {
        $map = [
            'budget_office_earmarking' => 1,    // Budget Office earmarks funds first
            'ceo_initial_approval' => 2,        // CEO reviews after budget earmarking
            'bac_evaluation' => 3,              // BAC evaluates quotations
            'bac_award_recommendation' => 4,    // BAC recommends award
            'ceo_final_approval' => 5,          // CEO final approval (optional)
            'po_generation' => 6,               // Purchase Order creation
            'po_approval' => 7,                 // PO approval
            'supply_office_review' => 8,        // Legacy/fallback step
        ];

        return $map[$stepName] ?? 0;
    }

    /**
     * Create a pending approval for a role and notify the assigned approver.
     */
    public static function createPendingForRole(PurchaseRequest $purchaseRequest, string $stepName, string $roleName): ?WorkflowApproval
    {
        $approver = User::role($roleName)->orderBy('id')->first();
        if (!$approver) {
            return null;
        }

        $approval = WorkflowApproval::firstOrCreate(
            [
                'purchase_request_id' => $purchaseRequest->id,
                'step_name' => $stepName,
            ],
            [
                'step_order' => self::getStepOrder($stepName),
                'approver_id' => $approver->id,
                'status' => 'pending',
                'assigned_at' => now(),
            ]
        );

        // Notify approver
        $approver->notify(new PurchaseRequestActionRequired($purchaseRequest, $stepName));

        return $approval;
    }
}


