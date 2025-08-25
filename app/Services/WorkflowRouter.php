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
     */
    public static function getStepOrder(string $stepName): int
    {
        $map = [
            'supply_office_review' => 1,
            'budget_office_earmarking' => 2,
            'ceo_initial_approval' => 3,
            'bac_evaluation' => 4,
            'bac_award_recommendation' => 5,
            'ceo_final_approval' => 6,
            'po_generation' => 7,
            'po_approval' => 8,
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


