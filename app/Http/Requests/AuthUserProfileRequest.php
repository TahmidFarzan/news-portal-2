<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AuthUserProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'name'              => ["required", "max:255"],
            'birth_date'        => ["nullable", "date"],
            'gender'            => ["required", "in:Male,Female,Other"],
            'religion'          => ["required", "in:Islam,Hindu,Christian,Other"],
            'marital_status'    => ["required", "in:Single,Married,Divorce,Separated,Other"],
            'mobile'            => ["nullable", "max:20", "regex:/^[+0-9 ]+$/"],

            'address'           => ["nullable"],
        ];
    }

    public function messages()
    {
        return [
            'name.required'           => __('form-requests.auth.user_profile.name.required'),
            'name.max'                => __('form-requests.auth.user_profile.name.max'),

            'birth_date.date'         => __('form-requests.auth.user_profile.birth_date.date'),

            'gender.required'         => __('form-requests.auth.user_profile.gender.required'),
            'religion.required'       => __('form-requests.auth.user_profile.religion.required'),
            'marital_status.required' => __('form-requests.auth.user_profile.marital_status.required'),

            'mobile.max'              => __('form-requests.auth.user_profile.mobile.max'),
            'mobile.regex'            => __('form-requests.auth.user_profile.mobile.regex'),
        ];
    }
}
