<?php
namespace App\Http\Requests;

use App\Helpers\PageHelper;
use App\Models\Language;
use App\Models\Page;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PageRequest extends FormRequest
{
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
            "title"          => ["required", "string", "max:200", Rule::unique('pages')->ignore($this->route('slug'), 'slug')],
            "language_id"    => ["required", "integer"],

            "default_use_as" => ["nullable"],

            "brief"          => ["nullable"],
            'body'           => ['nullable', 'required_if:is_default,false'],
            "seo_title"      => ["nullable"],
            "seo_brief"      => ["nullable"],
            "seo_keywords"   => ["nullable"],

        ];
    }

    public function messages()
    {
        return [
            "title.required"   => __("form-requests.page.title.required"),
            "title.string"     => __("form-requests.page.title.string"),
            "title.max"        => __("form-requests.page.title.max"),
            "body.required_if" => __("form-requests.page.body.required"),
        ];
    }

    public function withValidator($validator)
    {
        $page = Page::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($page) {
            $data = $validator->getData();

            $allowedDefaultUseAs = [
                PageHelper::DAFAULT_USE_AS_HOME,
                PageHelper::DAFAULT_USE_AS_LATEST,
                PageHelper::DAFAULT_USE_AS_SEARCH,
            ];

            if (! empty($data["language_id"])) {
                $languageQuery = Language::where("id", $data["language_id"]);

                if ($languageQuery->count() === 0) {
                    $validator->errors()->add(
                        'language_id',
                        __("form-requests.page.language_id.not_found")
                    );
                }
            }

            if (! empty($data["title"])) {
                $sameQuery = Page::where("title", $data["title"]);

                $sameQuery->where("language_id", $data["language_id"]);

                if (! empty($data["default_use_as"])) {
                    $sameQuery->where("default_use_as", $data["default_use_as"]);
                }

                if ($page) {
                    $sameQuery->where("id", "!=", $page->id);
                }

                if ($sameQuery->count() > 0) {
                    $validator->errors()->add(
                        'title',
                        __("form-requests.page.title.unique")
                    );
                }
            }

            if (! empty($data["default_use_as"])) {

                if (! in_array($data["default_use_as"], $allowedDefaultUseAs)) {
                    $validator->errors()->add(
                        'default_use_as',
                        __('form-requests.page.default_use_as.in', [
                            'values' => implode(', ', $allowedDefaultUseAs),
                        ])
                    );
                }
            }
        });
    }

}
