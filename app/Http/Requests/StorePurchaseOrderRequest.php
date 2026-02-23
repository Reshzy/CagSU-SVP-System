<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'pr_item_group_id' => ['nullable', 'exists:pr_item_groups,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'quotation_id' => ['nullable', 'exists:quotations,id'],
            'tin' => ['nullable', 'string', 'max:255'],
            'supplier_name_override' => ['nullable', 'string', 'max:255'],
            'funds_cluster' => ['nullable', 'string', 'max:255'],
            'funds_available' => ['nullable', 'numeric', 'min:0'],
            'ors_burs_no' => ['nullable', 'string', 'max:255'],
            'ors_burs_date' => ['nullable', 'date'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'delivery_address' => ['required', 'string'],
            'delivery_date_required' => ['required', 'date'],
            'terms_and_conditions' => ['required', 'string'],
            'special_instructions' => ['nullable', 'string'],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Please select a supplier.',
            'delivery_address.required' => 'Delivery address is required.',
            'delivery_date_required.required' => 'Delivery date is required.',
            'terms_and_conditions.required' => 'Terms and conditions are required.',
        ];
    }
}
