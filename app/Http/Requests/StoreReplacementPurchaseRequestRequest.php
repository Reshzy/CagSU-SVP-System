<?php

namespace App\Http\Requests;

use App\Models\PurchaseRequest;
use Illuminate\Support\Facades\Auth;

class StoreReplacementPurchaseRequestRequest extends StorePurchaseRequestRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // First check parent authorization (user must have department)
        if (! parent::authorize()) {
            return false;
        }

        // Get the original PR from route parameter
        $originalPr = $this->route('originalPr');

        // Ensure the PR is returned and belongs to the current user
        if (! $originalPr instanceof PurchaseRequest) {
            return false;
        }

        if ($originalPr->status !== 'returned_by_supply') {
            return false;
        }

        if ($originalPr->requester_id !== Auth::id()) {
            return false;
        }

        return true;
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return array_merge(parent::messages(), [
            // Add any replacement-specific messages here
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $originalPr = $this->route('originalPr');

            if ($originalPr && $originalPr->status !== 'returned_by_supply') {
                $validator->errors()->add('originalPr', 'Can only create replacement for returned purchase requests.');
            }

            if ($originalPr && $originalPr->requester_id !== Auth::id()) {
                $validator->errors()->add('originalPr', 'Unauthorized to create replacement for this purchase request.');
            }
        });
    }
}
