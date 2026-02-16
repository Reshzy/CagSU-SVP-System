<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Document $document): bool
    {
        // Public documents can be viewed by anyone authenticated
        if ($document->is_public) {
            return true;
        }

        // If document is attached to a PurchaseRequest, defer to PurchaseRequestPolicy
        if ($document->documentable_type === \App\Models\PurchaseRequest::class) {
            $purchaseRequest = $document->documentable;
            if ($purchaseRequest) {
                // Use the PurchaseRequestPolicy to determine access
                return $user->can('view', $purchaseRequest);
            }
        }

        // Check visible_to_roles if specified
        if ($document->visible_to_roles && is_array($document->visible_to_roles)) {
            foreach ($document->visible_to_roles as $role) {
                if ($user->hasRole($role)) {
                    return true;
                }
            }
        }

        // Document uploader can always view their own documents
        if ($document->uploaded_by === $user->id) {
            return true;
        }

        // System Admin and Executive Officer can view all documents
        if ($user->hasAnyRole(['System Admin', 'Executive Officer'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Document $document): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Document $document): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Document $document): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Document $document): bool
    {
        return false;
    }
}
