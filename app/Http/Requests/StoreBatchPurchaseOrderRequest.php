<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBatchPurchaseOrderRequest extends FormRequest
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
            'purchase_orders' => ['required', 'array', 'min:1'],
            'purchase_orders.*.supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_orders.*.quotation_id' => ['required', 'exists:quotations,id'],
            'purchase_orders.*.tin' => ['nullable', 'string', 'max:255'],
            'purchase_orders.*.supplier_name_override' => ['nullable', 'string', 'max:255'],
            'purchase_orders.*.funds_cluster' => ['required', 'string', 'max:255'],
            'purchase_orders.*.funds_available' => ['required', 'numeric', 'min:0'],
            'purchase_orders.*.ors_burs_no' => ['required', 'string', 'max:255'],
            'purchase_orders.*.ors_burs_date' => ['required', 'date'],
            'purchase_orders.*.total_amount' => ['required', 'numeric', 'min:0'],
            'purchase_orders.*.delivery_address' => ['required', 'string'],
            'purchase_orders.*.delivery_date_required' => ['required', 'date', 'after_or_equal:today'],
            'purchase_orders.*.terms_and_conditions' => ['required', 'string'],
            'purchase_orders.*.special_instructions' => ['nullable', 'string'],
            'purchase_orders.*.items' => ['required', 'array', 'min:1'],
            'purchase_orders.*.items.*.purchase_request_item_id' => ['required', 'exists:purchase_request_items,id'],
            'purchase_orders.*.items.*.quotation_item_id' => ['required', 'exists:quotation_items,id'],
            'purchase_orders.*.items.*.quantity' => ['required', 'integer', 'min:1'],
            'purchase_orders.*.items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'purchase_orders.*.items.*.total_price' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom error messages for validation rules
     */
    public function messages(): array
    {
        return [
            'purchase_orders.required' => 'At least one purchase order is required.',
            'purchase_orders.*.supplier_id.required' => 'Supplier is required for each purchase order.',
            'purchase_orders.*.funds_cluster.required' => 'Funds cluster is required.',
            'purchase_orders.*.funds_available.required' => 'Funds available is required.',
            'purchase_orders.*.ors_burs_no.required' => 'ORS/BURS number is required.',
            'purchase_orders.*.ors_burs_date.required' => 'ORS/BURS date is required.',
            'purchase_orders.*.total_amount.required' => 'Total amount is required.',
            'purchase_orders.*.delivery_address.required' => 'Delivery address is required.',
            'purchase_orders.*.delivery_date_required.required' => 'Delivery date is required.',
            'purchase_orders.*.delivery_date_required.after_or_equal' => 'Delivery date must be today or later.',
            'purchase_orders.*.terms_and_conditions.required' => 'Terms and conditions are required.',
            'purchase_orders.*.items.required' => 'Each purchase order must have at least one item.',
        ];
    }
}
