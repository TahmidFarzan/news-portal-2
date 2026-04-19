<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
     public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required'    => __('form-requests.auth.login.email.required'),
            'email.email'       => __('form-requests.auth.login.email.email'),
            'email.exists'      => __('form-requests.auth.login.email.exists'),

            'password.required' => __('form-requests.auth.login.password.required'),
        ];
    }
}
