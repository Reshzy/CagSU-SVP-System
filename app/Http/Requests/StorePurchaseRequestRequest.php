<?php

namespace App\Http\Requests;

use App\Models\DepartmentBudget;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePurchaseRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = Auth::user();

        // User must be assigned to a department
        if (! $user || ! $user->department_id) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'purpose' => ['required', 'string', 'max:255'],
            'justification' => ['nullable', 'string'],

            // Multiple items from PPMP or custom
            'items' => ['required', 'array', 'min:1'],
            'items.*.ppmp_item_id' => ['nullable', 'exists:ppmp_items,id'],
            'items.*.item_code' => ['nullable', 'string', 'max:100'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.detailed_specifications' => ['nullable', 'string'],
            'items.*.unit_of_measure' => ['required', 'string', 'max:50'],
            'items.*.quantity_requested' => ['required', 'integer', 'min:1'],
            'items.*.estimated_unit_cost' => ['required', 'numeric', 'min:0'],

            // Attachments (optional)
            'attachments.*' => ['file', 'max:10240'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'purpose.required' => 'The purpose of the purchase request is required.',
            'purpose.max' => 'The purpose may not be greater than 255 characters.',
            'justification.string' => 'The justification must be a valid text.',

            'items.required' => 'At least one item must be selected for the purchase request.',
            'items.min' => 'At least one item must be selected for the purchase request.',
            'items.*.ppmp_item_id.exists' => 'One or more selected PPMP items do not exist.',
            'items.*.item_name.required' => 'Item name is required for all items.',
            'items.*.item_name.max' => 'Item name may not be greater than 255 characters.',
            'items.*.unit_of_measure.required' => 'Unit of measure is required for all items.',
            'items.*.quantity_requested.required' => 'Quantity is required for all items.',
            'items.*.quantity_requested.integer' => 'Quantity must be a valid number.',
            'items.*.quantity_requested.min' => 'Quantity must be at least 1.',
            'items.*.estimated_unit_cost.required' => 'Unit cost is required for all items.',
            'items.*.estimated_unit_cost.numeric' => 'Unit cost must be a valid number.',
            'items.*.estimated_unit_cost.min' => 'Unit cost must be at least 0.',

            'attachments.*.file' => 'All attachments must be valid files.',
            'attachments.*.max' => 'Each attachment may not be greater than 10MB.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'items.*.ppmp_item_id' => 'PPMP item',
            'items.*.item_code' => 'item code',
            'items.*.item_name' => 'item name',
            'items.*.detailed_specifications' => 'specifications',
            'items.*.unit_of_measure' => 'unit of measure',
            'items.*.quantity_requested' => 'quantity',
            'items.*.estimated_unit_cost' => 'unit cost',
        ];
    }

    /**
     * Calculate the total cost of all items in the request.
     */
    public function calculateTotalCost(): float
    {
        $totalCost = 0;
        $items = $this->validated()['items'] ?? [];

        foreach ($items as $item) {
            $totalCost += (float) ($item['estimated_unit_cost'] ?? 0) * (int) ($item['quantity_requested'] ?? 0);
        }

        return $totalCost;
    }

    /**
     * Check if the budget can accommodate this purchase request.
     */
    public function checkBudgetAvailability(): array
    {
        $user = Auth::user();

        if (! $user->department_id) {
            return [
                'can_reserve' => false,
                'error' => 'User must be assigned to a department.',
            ];
        }

        $fiscalYear = date('Y');
        $budget = DepartmentBudget::getOrCreateForDepartment($user->department_id, $fiscalYear);
        $totalCost = $this->calculateTotalCost();

        if (! $budget->canReserve($totalCost)) {
            return [
                'can_reserve' => false,
                'error' => 'Insufficient budget. Available: ₱'.number_format($budget->getAvailableBudget(), 2).', Required: ₱'.number_format($totalCost, 2),
                'available' => $budget->getAvailableBudget(),
                'required' => $totalCost,
            ];
        }

        return [
            'can_reserve' => true,
            'available' => $budget->getAvailableBudget(),
            'required' => $totalCost,
        ];
    }
}
