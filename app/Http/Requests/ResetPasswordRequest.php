<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'    => ['required'],
            'email'    => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'     => __('form-requests.auth.reset_password.token.required'),

            'email.required'     => __('form-requests.auth.reset_password.email.required'),
            'email.email'        => __('form-requests.auth.reset_password.email.email'),
            'email.exists'       => __('form-requests.auth.reset_password.email.exists'),

            'password.required'  => __('form-requests.auth.reset_password.password.required'),
            'password.min'       => __('form-requests.auth.reset_password.password.min'),
            'password.confirmed' => __('form-requests.auth.reset_password.password.confirmed'),
        ];
    }
}
