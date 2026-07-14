<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterTenantRequest extends FormRequest
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
            'company_name'   => ['required', 'string', 'max:255'],
            'domain'         => ['required', 'string', 'alpha_dash', 'max:255', 'unique:tenants,id'],
            'admin_name'     => ['required', 'string', 'max:255'],
            'admin_email'    => ['required', 'string', 'email', 'max:255'],
            'admin_password' => ['required', 'string', 'min:8'],
            'plan_id'        => ['required', 'exists:subscription_plans,id'],
        ];
    }
}
