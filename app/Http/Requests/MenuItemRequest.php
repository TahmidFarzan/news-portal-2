<?php
namespace App\Http\Requests;

use App\Helpers\SystemHelper;
use App\Models\Category;
use App\Models\Language;
use App\Models\MenuItem;
use App\Models\Tag;
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
            "url"         => ["nullable", "url"],
            "parent_id"   => ["nullable", "integer"],
            "position"    => ["nullable", "integer"],
        ];
    }

    public function messages(): array
    {
        return [
            "name.required"    => __("form-requests.menu_item.name.required"),
            "name.string"      => __("form-requests.menu_item.name.string"),
            "name.max"         => __("form-requests.menu_item.name.max"),

            "url.url"          => __("form-requests.menu_item.url.url"),
            "position.integer" => __("form-requests.menu_item.position.integer"),
        ];
    }

    public function withValidator($validator): void
    {
        $menuItem = MenuItem::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($menuItem) {
            $data = $validator->getData();

            if (! empty($data["has_parent"])) {
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

            if (! empty($data["is_custom_url"])) {
                if (empty($data["url"])) {
                    $validator->errors()->add(
                        'url',
                        __("form-requests.menu_item.url.required_if")
                    );
                }

                return;
            }

            if (empty($data["is_custom_url"])) {
                if (empty($data["model_type"])) {
                    $validator->errors()->add(
                        'model_type',
                        __("form-requests.menu_item.model_type.required_if")
                    );
                }

                if (empty($data["model_id"])) {
                    $validator->errors()->add(
                        'model_id',
                        __("form-requests.menu_item.model_id.required_if")
                    );
                }
            }

            if (! empty($data["model_type"]) && ! empty($data["model_id"])) {
                if ($data["model_type"] == SystemHelper::MENU_ITEM_MODEL_CATEGORY) {
                    $categoryCount = Category::where("id", $data["model_id"])->count();

                    if ($categoryCount == 0) {
                        $validator->errors()->add(
                            'model_id',
                            __("form-requests.menu_item.model_id.not_found")
                        );
                    }
                }

                if ($data["model_type"] == SystemHelper::MENU_ITEM_MODEL_TAG) {
                    $tagCount = Tag::where("id", $data["model_id"])->count();

                    if ($tagCount == 0) {
                        $validator->errors()->add(
                            'model_id',
                            __("form-requests.menu_item.model_id.not_found")
                        );
                    }
                }
            }

            if (! empty($data["language_id"])) {
                $languageCount = Language::where("id", $data["language_id"])->count();

                if ($languageCount === 0) {
                    $validator->errors()->add(
                        'language_id',
                        __("form-requests.menu_item.language_id.not_found")
                    );
                }
            }
        });
    }
}
