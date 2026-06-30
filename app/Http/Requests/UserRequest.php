<?php
namespace App\Http\Requests;

use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ["required", "max:255"],

            'email'                 => ["required", "email", "max:255"],
            'mobile'                => ["nullable", "max:20", "regex:/^[+0-9 ]+$/"],

            'birth_date'            => ["nullable", "date"],
            'gender'                => ["required", "in:Male,Female,Other"],
            'religion'              => ["required", "in:Islam,Hindu,Christian,Other"],
            'marital_status'        => ["required", "in:Single,Married,Divorce,Separated,Other"],

            'address'               => ["nullable"],

            "set_as_verify_email"   => ["required", "boolean"],
            "change_password"       => ["required", "boolean"],

            'user_permission_ids'   => ["nullable"],

            "password"              => ["nullable", "string", "min:8", "confirmed"],
            "password_confirmation" => ["nullable", "string"],

            'profile_image'         => ["nullable", "image", "mimes:jpg,jpeg,png,webp"],
        ];
    }

    public function messages()
    {
        return [
            'name.required'                => __('form-requests.user.name.required'),
            'name.string'                  => __('form-requests.user.name.string'),
            'name.max'                     => __('form-requests.user.name.max'),

            'email.required'               => __('form-requests.user.email.required'),
            'email.string'                 => __('form-requests.user.email.string'),
            'email.max'                    => __('form-requests.user.email.max'),
            'email.unique'                 => __('form-requests.user.email.unique'),

            'birth_date.date'              => __('form-requests.user.birth_date.date'),

            'gender.required'              => __('form-requests.user.gender.required'),
            'religion.required'            => __('form-requests.user.religion.required'),
            'marital_status.required'      => __('form-requests.user.marital_status.required'),

            'mobile.max'                   => __('form-requests.user.mobile.max'),
            'mobile.regex'                 => __('form-requests.user.mobile.regex'),

            'change_password.required'     => __('form-requests.user.change_password.required'),
            'change_password.boolean'      => __('form-requests.user.change_password.boolean'),

            'set_as_verify_email.required' => __('form-requests.user.set_as_verify_email.required'),
            'set_as_verify_email.boolean'  => __('form-requests.user.set_as_verify_email.boolean'),

            'password.string'              => __('form-requests.user.password.string'),
            'password.min'                 => __('form-requests.user.password.min'),
            'password.confirmed'           => __('form-requests.user.password.confirmed'),

            'profile_image.image'          => __('form-requests.user.profile_image.image'),
            'profile_image.mimes'          => __('form-requests.user.profile_image.mimes'),
            'profile_image.dimensions'     => __('form-requests.user.profile_image.dimensions'),

        ];
    }

    public function withValidator($validator)
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::user();

        $user = User::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($user, $authUser) {
            $aVData = $validator->getData();

            if (! $aVData['is_super_admin']){

                if (! array_key_exists(
                    'user_permission_ids',
                    $aVData
                )) {
                    $validator->errors()->add(
                        'user_permission_ids',
                        __('form-requests.user.user_permission_ids.required')
                    );
                }
            }

            if (array_key_exists('user_permission_ids', $aVData)){
                $permissionIds = array_filter( (array) ( $aVData[ 'user_permission_ids'] ?? []) );

                if (count($permissionIds)) {

                    $count = UserPermission::whereIn('id', $permissionIds)->count();

                    if ($count !== count($permissionIds)) {
                        $validator ->errors()->add('user_permission_ids',__('form-requests.user.user_permission_ids.not_found'));
                    }
                }
            }

            if (isset($aVData["email"])) {
                $sameCount = User::where("email", $aVData["email"]);
                if ($user) {
                    $sameCount = $sameCount->where("id", "!=", $user->id);
                }

                $sameCount = $sameCount->count();

                if ($sameCount > 0) {
                    $validator->errors()->add(
                        'email',
                        __("form-requests.user.email.unique"),
                    );
                }
            }

            if (isset($aVData["change_password"]) && $aVData["change_password"] == true) {
                if (! isset($aVData["password"]) || empty($aVData['password'])) {
                    $validator->errors()->add(
                        'password',
                        __("form-requests.auth.user_account.change_password.required"),
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
