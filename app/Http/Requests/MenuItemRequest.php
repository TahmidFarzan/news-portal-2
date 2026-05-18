<?php
namespace App\Http\Requests;

use App\Models\MenuItem;
use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MenuItemRequest extends FormRequest
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
            "name"        => ["required", "string", "max:200"],
            "language_id" => ["required", "integer"],
            "url"         => ["nullable", "string"],
            "parent_id"   => ["nullable", "required_if:has_parent,true", "integer"],
        ];
    }

    public function messages()
    {
        return [
            "name.required"         => __("form-requests.menu_item.name.required"),
            "name.string"           => __("form-requests.menu_item.name.string"),
            "name.max"              => __("form-requests.menu_item.name.max"),

            "parent_id.required_if" => __("form-requests.menu_item.parent_id.required"),
        ];
    }

    public function withValidator($validator)
    {
        $menuItem = MenuItem::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($menuItem) {
            $data = $validator->getData();

            if (!empty($data["has_parent"])) {
                if (empty($data["parent_id"])) {
                    $validator->errors()->add(
                        'parent_id',
                        __("form-requests.menu_item.parent_id.required")
                    );
                } else {
                    $parentQuery = MenuItem::where("id", $data["parent_id"]);

                    if ($menuItem) {
                        $parentQuery->where("id", "!=", $menuItem->id);
                    }

                    if ($parentQuery->count() === 0) {
                        $validator->errors()->add(
                            'parent_id',
                            __("form-requests.menu_item.parent_id.not_found")
                        );
                    }
                }
            }

            if (!empty($data["language_id"])) {
                $languageQuery = Language::where("id", $data["language_id"]);

                if ($languageQuery->count() === 0) {
                    $validator->errors()->add(
                        'language_id',
                        __("form-requests.menu_item.language_id.not_found")
                    );
                }
            }
        });
    }
}
