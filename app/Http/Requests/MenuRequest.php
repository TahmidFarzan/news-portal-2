<?php
namespace App\Http\Requests;

use App\Models\Language;
use App\Models\MenuType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "name"         => ["required", "string", "max:200"],
            "language_id"  => ["required", "integer"],
            "menu_type_id" => ["required", "integer"],
        ];
    }

    public function messages()
    {
        return [
            "name.required"         => __("form-requests.menu.name.required"),
            "name.string"           => __("form-requests.menu.name.string"),
            "name.max"              => __("form-requests.menu.name.max"),

            "language_id.required"  => __("form-requests.menu.language_id.required"),
            "menu_type_id.required" => __("form-requests.menu.menu_type_id.required"),
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            if (! empty($data["language_id"])) {
                $languageQuery = Language::where("id", $data["language_id"]);

                if ($languageQuery->count() === 0) {
                    $validator->errors()->add(
                        'language_id',
                        __("form-requests.menu.language_id.not_found")
                    );
                }
            }

            if (! empty($data["menu_type_id"])) {
                $menuTypeQuery = MenuType::where("id", $data["menu_type_id"]);

                if ($menuTypeQuery->count() === 0) {
                    $validator->errors()->add(
                        'menu_type_id',
                        __("form-requests.menu.menu_type_id.not_found")
                    );
                }
            }

        });
    }
}
