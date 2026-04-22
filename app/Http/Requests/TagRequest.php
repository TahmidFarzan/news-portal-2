<?php

namespace App\Http\Requests;

use App\Models\Language;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class TagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            "name"         => ["required", "string", "max:200", Rule::unique('tags')->ignore($this->route('slug'), 'slug')],
            "language_id"  => ["required", "integer"],

            "details"      => ["nullable"],
            "seo_title"    => ["nullable"],
            "seo_brief"    => ["nullable"],
            "seo_keywords" => ["nullable"],
        ];
    }

    public function messages()
    {
        return [
            "name.required"       => __("form-requests.tag.name.required"),
            "name.string"         => __("form-requests.tag.name.string"),
            "name.max"            => __("form-requests.tag.name.max"),
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            if (!empty($data["language_id"])) {
                $languageQuery = Language::where("id", $data["language_id"]);

                if ($languageQuery->count() === 0) {
                    $validator->errors()->add(
                        'language_id',
                        __("form-requests.tag.language_id.not_found")
                    );
                }
            }

        });
    }
}
