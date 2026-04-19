<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AuthUserAccountRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            "name"                  => ["required", "string", "max:200"],
            'email'                 => ["email", "max:255", Rule::unique('users')->ignore(Auth::id(), 'id')],

            "change_password"       => ["required", "boolean"],
            "password"              => ["nullable", "string", "min:8", "confirmed"],
            "current_password"      => ["nullable", "string"],
            "password_confirmation" => ["nullable", "string"],
        ];
    }

    public function messages()
    {
        return [
            'name.required'                     => __('form-requests.auth.user_account.name.required'),
            'name.string'                       => __('form-requests.auth.user_account.name.string'),
            'name.max'                          => __('form-requests.auth.user_account.name.max'),

            'email.required'                    => __('form-requests.auth.user_account.email.required'),
            'email.string'                      => __('form-requests.auth.user_account.email.string'),
            'email.max'                         => __('form-requests.auth.user_account.email.max'),
            'email.unique'                      => __('form-requests.auth.user_account.email.unique'),

            'password.required_if'              => __('form-requests.auth.user_account.password.required_if'),
            'password.string'                   => __('form-requests.auth.user_account.password.string'),
            'password.min'                      => __('form-requests.auth.user_account.password.min'),
            'password.confirmed'                => __('form-requests.auth.user_account.password.confirmed'),
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $aVData = $validator->getData();

            if (isset($aVData["change_password"]) && $aVData["change_password"] == true) {
                if (! isset($aVData["password"]) || empty($aVData['password'])) {
                    $validator->errors()->add(
                        'password', __("form-requests.auth.user_account.password.required"),
                    );
                }

                if (! isset($aVData["current_password"]) || empty($aVData['current_password'])) {
                    $validator->errors()->add(
                        'current_password',
                        __("form-requests.auth.user_account.current_password.required"),
                    );
                }

                if (! isset($aVData["password_confirmation"]) || empty($aVData['password_confirmation'])) {
                    $validator->errors()->add(
                        'password_confirmation',
                        __("form-requests.auth.user_account.password_confirmation.required"),
                    );
                }

            }

        });
    }
}
