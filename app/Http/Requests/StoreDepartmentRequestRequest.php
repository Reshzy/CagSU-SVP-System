<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDepartmentRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:10', 'alpha_num', 'unique:departments,code', 'unique:department_requests,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'head_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:30'],
            'requester_email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'A department name is required.',
            'code.required' => 'A department code is required.',
            'code.alpha_num' => 'The department code may only contain letters and numbers.',
            'code.unique' => 'This department code already exists or has already been requested.',
            'head_name.required' => 'The department head name is required.',
            'contact_email.email' => 'Please enter a valid contact email address.',
            'requester_email.required' => 'Your email address is required so we can link your request.',
            'requester_email.email' => 'Please enter a valid email address.',
        ];
    }
}
