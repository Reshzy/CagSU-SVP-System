<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class BudgetEarmarkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->hasRole('Budget Office');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'approved_budget_total' => ['required', 'numeric', 'min:0'],
            'date_needed' => ['required', 'date'],
            'fund_cluster_code' => ['nullable', 'string', 'in:01,05,06,07', 'required_with:fund_details'],
            'fund_details' => ['nullable', 'string', 'max:255'],
            'procurement_type' => ['required', 'in:supplies_materials,equipment,infrastructure,services,consulting_services'],
            'remarks' => ['required', 'string', 'min:1'],
            'pr_title' => ['nullable', 'string', 'max:255'],
            'legal_basis' => ['nullable', 'string', 'max:500'],
            'earmark_programs_activities' => ['nullable', 'string', 'max:1000'],
            'earmark_responsibility_center' => ['nullable', 'string', 'max:255'],
            'earmark_date_to' => ['nullable', 'date', 'after_or_equal:date_needed'],
            'earmark_object_expenditures' => ['nullable', 'array'],
            'earmark_object_expenditures.*.code' => ['nullable', 'string', 'max:50'],
            'earmark_object_expenditures.*.description' => ['nullable', 'string', 'max:255'],
            'earmark_object_expenditures.*.amount' => ['nullable', 'numeric', 'min:0'],
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
            'approved_budget_total.required' => 'Approved budget total is required.',
            'approved_budget_total.numeric' => 'Approved budget total must be a valid number.',
            'date_needed.required' => 'Date needed is required.',
            'procurement_type.required' => 'Procurement type is required.',
            'procurement_type.in' => 'Selected procurement type is invalid.',
            'remarks.required' => 'Remarks are required to forward to CEO for approval.',
            'earmark_date_to.after_or_equal' => 'The earmark end date must be on or after the date needed.',
        ];
    }
}
