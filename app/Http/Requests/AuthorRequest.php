<?php
namespace App\Http\Requests;

use App\Models\Author;
use Illuminate\Foundation\Http\FormRequest;

class AuthorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            "name"            => ["required", "string", "max:200"],

            "brief"           => ["nullable"],
            "profile_details" => ["nullable"],

            "seo_title"       => ["nullable"],
            "seo_brief"       => ["nullable"],
            "seo_keywords"    => ["nullable"],

            "profile_image"   => ["nullable", "image", "mimetypes:image/*", "dimensions:ratio=1/1,min_width=512,min_height=512"],
        ];
    }

    public function messages()
    {
        return [
            "name.required"            => __("form-requests.author.name.required"),
            "name.string"              => __("form-requests.author.name.string"),
            "name.max"                 => __("form-requests.author.name.max"),

            "profile_image.image"      => __("form-requests.author.desktop_banner_image.image"),
            "profile_image.mimes"      => __("form-requests.author.desktop_banner_image.mimes"),
            "profile_image.dimensions" => __("form-requests.author.desktop_banner_image.dimensions"),
        ];
    }

    public function withValidator($validator)
    {
        $author = Author::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($author) {
            $data = $validator->getData();

            if (! empty($data["name"])) {
                $sameQuery = Author::where("name", $data["name"]);

                if ($author) {
                    $sameQuery->where("id", "!=", $author->id);
                }

                if ($sameQuery->count() > 0) {
                    $validator->errors()->add(
                        'name',
                        __("form-requests.author.name.unique")
                    );
                }
            }
        });
    }
}
