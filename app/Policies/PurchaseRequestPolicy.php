<?php

namespace App\Policies;

use App\Models\PurchaseRequest;
use App\Models\User;

class PurchaseRequestPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // All authenticated users can view PRs (scoped by role in controller)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PurchaseRequest $purchaseRequest): bool
    {
        // System Admin, Executive Officer, Supply Officer, Budget Office, BAC roles can view all PRs
        if ($user->hasAnyRole(['System Admin', 'Executive Officer', 'Supply Officer', 'Budget Office', 'BAC Chair', 'BAC Members', 'BAC Secretariat'])) {
            return true;
        }

        // Deans and End Users can only view their own college's PRs
        return $purchaseRequest->department_id === $user->department_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Deans and End Users can create PRs
        return $user->hasAnyRole(['Dean', 'End User', 'System Admin']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PurchaseRequest $purchaseRequest): bool
    {
        // System Admin can always update
        if ($user->hasRole('System Admin')) {
            return true;
        }

        // Users can only update their own draft PRs
        if ($purchaseRequest->requester_id === $user->id && $purchaseRequest->status === 'draft') {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        // System Admin can always delete
        if ($user->hasRole('System Admin')) {
            return true;
        }

        // Users can only delete their own draft PRs
        return $purchaseRequest->requester_id === $user->id && $purchaseRequest->status === 'draft';
    }

    /**
     * Determine whether the user can create a replacement PR.
     */
    public function createReplacement(User $user, PurchaseRequest $purchaseRequest): bool
    {
        // PR must be returned and belong to the user
        return $purchaseRequest->status === 'returned_by_supply'
            && $purchaseRequest->requester_id === $user->id
            && $purchaseRequest->department_id === $user->department_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->hasRole('System Admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PurchaseRequest $purchaseRequest): bool
    {
        return $user->hasRole('System Admin');
    }
}
