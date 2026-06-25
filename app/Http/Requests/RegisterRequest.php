<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'      => __('form-requests.auth.register.name.required'),

            'email.required'     => __('form-requests.auth.register.email.required'),
            'email.email'        => __('form-requests.auth.register.email.email'),
            'email.unique'       => __('form-requests.auth.register.email.unique'),

            'password.required'  => __('form-requests.auth.register.password.required'),
            'password.min'       => __('form-requests.auth.register.password.min'),
            'password.confirmed' => __('form-requests.auth.register.password.confirmed'),
        ];
    }
}
