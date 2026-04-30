<?php
namespace App\Http\Requests;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Helpers\UserHelper;

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

            'user_role_id'          => ["required"],

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

            'user_role_id.required'        => __('form-requests.user.user_role_id.required'),

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

            if (isset($aVData["user_role_id"])) {
                $userRole = UserRole::where("id", $aVData["user_role_id"])->first();
                if (! $userRole) {
                    $validator->errors()->add(
                        'user_role_id', __("form-requests.user.user_role_id.not_found"),
                    );
                }

                if ($userRole) {
                    if (! $authUser->hasUserRole(UserHelper::USER_ROLE_ADMIN)) {
                        $validator->errors()->add(
                            'user_role_id', __("form-requests.user.user_role_id.do_not_have_permission"),
                        );
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
