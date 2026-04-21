<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            "name"         => ["required", "string", "max:200"],
            "language_id"  => ["required", "integer"],

            "brief"        => ["nullable"],
            "details"      => ["nullable"],
            "seo_title"    => ["nullable"],
            "seo_brief"    => ["nullable"],
            "seo_keywords" => ["nullable"],

            "parent_id"    => ["nullable", "required_if:has_parent,true", "integer"],
        ];
    }

    public function messages()
    {
        return [
            "name.required"       => __("form-requests.category.name.required"),
            "name.string"         => __("form-requests.category.name.string"),
            "name.max"            => __("form-requests.category.name.max"),

            "parent_id.required_if" => __("form-requests.category.parent_id.required"),
        ];
    }

    public function withValidator($validator)
    {
        $category = Category::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($category) {
            $data = $validator->getData();

            if (!empty($data["has_parent"])) {
                if (empty($data["parent_id"])) {
                    $validator->errors()->add(
                        'parent_id',
                        __("form-requests.category.parent_id.required")
                    );
                } else {
                    $parentQuery = Category::where("id", $data["parent_id"]);

                    if ($category) {
                        $parentQuery->where("id", "!=", $category->id);
                    }

                    if ($parentQuery->count() === 0) {
                        $validator->errors()->add(
                            'parent_id',
                            __("form-requests.category.parent_id.not_found")
                        );
                    }
                }
            }

            if (!empty($data["language_id"])) {
                $languageQuery = Language::where("id", $data["language_id"]);

                if ($languageQuery->count() === 0) {
                    $validator->errors()->add(
                        'language_id',
                        __("form-requests.category.language_id.not_found")
                    );
                }
            }

            if (!empty($data["name"])) {
                $sameQuery = Category::where("name", $data["name"]);

                if ($category) {
                    $sameQuery->where("id", "!=", $category->id);
                }

                if (!empty($data["has_parent"])) {
                    $sameQuery->where("parent_id", $data["parent_id"]);
                } else {
                    $sameQuery->whereNull("parent_id");
                }

                if ($sameQuery->count() > 0) {
                    $validator->errors()->add(
                        'name',
                        __("form-requests.category.name.unique")
                    );
                }
            }
        });
    }
}
