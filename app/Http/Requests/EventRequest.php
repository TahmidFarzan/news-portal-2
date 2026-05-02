<?php
namespace App\Http\Requests;

use App\Models\Event;
use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            "name"                 => ["required", "string", "max:200"],
            "language_id"          => ["required", "integer"],

            "brief"              => ["nullable"],
            "seo_title"            => ["nullable"],
            "seo_brief"            => ["nullable"],
            "seo_keywords"         => ["nullable"],

            "desktop_banner_image" => ["nullable", "image", "mimetypes:image/*", "dimensions:width=1300,height=90"],
            "mobile_banner_image"  => ["nullable", "image", "mimetypes:image/*", "dimensions:width=400,height=90"],
        ];
    }

    public function messages()
    {
        return [
            "name.required"                   => __("form-requests.event.name.required"),
            "name.string"                     => __("form-requests.event.name.string"),
            "name.max"                        => __("form-requests.event.name.max"),

            "desktop_banner_image.image"      => __("form-requests.event.desktop_banner_image.image"),
            "desktop_banner_image.mimes"      => __("form-requests.event.desktop_banner_image.mimes"),
            "desktop_banner_image.dimensions" => __("form-requests.event.desktop_banner_image.dimensions"),

            "mobile_banner_image.image"      => __("form-requests.event.mobile_banner_image.image"),
            "mobile_banner_image.mimes"      => __("form-requests.event.mobile_banner_image.mimes"),
            "mobile_banner_image.dimensions" => __("form-requests.event.mobile_banner_image.dimensions"),
        ];
    }

    public function withValidator($validator)
    {
        $event = Event::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($event) {
            $data = $validator->getData();

            if (! empty($data["language_id"])) {
                $languageQuery = Language::where("id", $data["language_id"]);

                if ($languageQuery->count() === 0) {
                    $validator->errors()->add(
                        'language_id',
                        __("form-requests.event.language_id.not_found")
                    );
                }
            }

            if (! empty($data["name"])) {
                $sameQuery = Event::where("name", $data["name"]);

                if ($event) {
                    $sameQuery->where("id", "!=", $event->id);
                }

                if ($sameQuery->count() > 0) {
                    $validator->errors()->add(
                        'name',
                        __("form-requests.event.name.unique")
                    );
                }
            }
        });
    }
}
