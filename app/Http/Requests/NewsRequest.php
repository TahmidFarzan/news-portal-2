<?php
namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\News;
use App\Models\Tag;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class NewsRequest extends FormRequest
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
            "language_id"                       => ["required"],
            "category_id"                       => ["required"],

            "title"                             => ["required"],
            "brief"                             => ["required"],

            "sub_title"                         => ["nullable"],
            "content_shoulder"                  => ["nullable"],

            "event_id"                          => ["nullable"],
            "location_id"                       => ["nullable"],

            "tag_ids"                           => ["nullable"],
            "contributor_ids"                   => ["nullable"],

            "seo_title"                         => ["nullable"],
            "seo_brief"                         => ["nullable"],
            "seo_keywords"                      => ["nullable"],

            "writer"                            => ["nullable"],
            "source"                            => ["nullable"],

            "feature_image_caption"             => ["required"],
            "upload_feature_image"              => [
                "nullable",
                "image",
                "mimes:jpg,jpeg,png,webp",
                "dimensions:ratio=16/9,width=1280,height=720",
            ],
            "selected_feature_image_url"        => ["nullable", "url"],

            "upload_feature_image_mobile"       => [
                "nullable",
                "image",
                "mimes:jpg,jpeg,png,webp",
                "dimensions:ratio=16/9,width=400,height=225",
            ],
            "selected_feature_image_mobile_url" => ["nullable", "url"],

            "body"                              => ["required"],

            "page_section"                      => ["nullable"],

            "editor_media_ids"                  => ["nullable"],
        ];
    }

    public function messages(): array
    {
        return [
            "title.required"                         => __("form-requests.news.title.required"),
            "title.string"                           => __("form-requests.news.title.string"),
            "title.max"                              => __("form-requests.news.title.max"),

            "body.required"                          => __("form-requests.news.body.required"),

            "language_id.required"                   => __("form-requests.news.language_id.required"),
            "category_id.required"                   => __("form-requests.news.category_id.required"),

            "feature_image_caption.required"         => __("form-requests.news.feature_image_caption.required"),

            "upload_feature_image.image"             => __("form-requests.news.upload_feature_image.image"),
            "upload_feature_image.mimes"             => __("form-requests.news.upload_feature_image.image"),
            "upload_feature_image.dimensions"        => __("form-requests.news.upload_feature_image.dimensions"),

            "selected_feature_image_url.url"         => __("form-requests.news.selected_feature_image_url.url"),

            "upload_feature_image_mobile.image"      => __("form-requests.news.upload_feature_image_mobile.image"),
            "upload_feature_image_mobile.mimes"      => __("form-requests.news.upload_feature_image_mobile.image"),
            "upload_feature_image_mobile.dimensions" => __("form-requests.news.upload_feature_image_mobile.dimensions"),

            "selected_feature_image_mobile_url.url"  => __("form-requests.news.selected_feature_image_mobile_url.url"),
        ];
    }

    public function withValidator($validator): void
    {
        $news = News::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($news) {
            $data = $validator->getData();

            if (! empty($data['title'])) {
                $dates = [
                    now()->format('Y-m-d'),
                    now()->subDay()->format('Y-m-d'),
                ];

                foreach ($dates as $date) {
                    $storiesQuery = News::where('title', $data['title'])
                        ->whereDate('created_at', $date);

                    if ($news) {
                        $storiesQuery->where('id', '!=', $news->id);
                    }

                    if ($storiesQuery->exists()) {
                        $validator->errors()->add(
                            'title',
                            __("form-requests.news.title.unique")
                        );

                        break;
                    }
                }
            }

            if (! empty($data["language_id"])) {
                if (! Language::where("id", $data["language_id"])->exists()) {
                    $validator->errors()->add(
                        'language_id',
                        __("form-requests.news.language_id.not_found")
                    );
                }
            }

            if (! empty($data["category_id"])) {
                if (! Category::where("id", $data["category_id"])->exists()) {
                    $validator->errors()->add(
                        'category_id',
                        __("form-requests.news.category_id.not_found")
                    );
                }
            }

            if (! empty($data["event_id"])) {
                if (! Event::where("id", $data["event_id"])->exists()) {
                    $validator->errors()->add(
                        'event_id',
                        __("form-requests.news.event_id.not_found")
                    );
                }
            }

            if (! empty($data["location_id"])) {
                if (! Location::where("id", $data["location_id"])->exists()) {
                    $validator->errors()->add(
                        'location_id',
                        __("form-requests.news.location_id.not_found")
                    );
                }
            }

            $tagIds = $this->normalizeIds($data['tag_ids'] ?? []);

            foreach ($tagIds as $tagId) {
                if (! Tag::where('id', $tagId)->exists()) {
                    $validator->errors()->add(
                        'tag_ids',
                        __("form-requests.news.tag_ids.not_found")
                    );

                    break;
                }
            }

            $contributorIds = $this->normalizeIds($data['contributor_ids'] ?? []);

            foreach ($contributorIds as $contributorId) {
                if (! Contributor::where('id', $contributorId)->exists()) {
                    $validator->errors()->add(
                        'contributor_ids',
                        __("form-requests.news.contributor_ids.not_found")
                    );

                    break;
                }
            }

            if (! $news?->feature_image && ! $this->hasFile('upload_feature_image') && ! empty($data['selected_feature_image_url'])) {
                $validator->errors()->add(
                    'upload_feature_image',
                    __("form-requests.news.upload_feature_image.select_one")
                );

                $validator->errors()->add(
                    'selected_feature_image_url',
                    __("form-requests.news.selected_feature_image_url.select_one")
                );
            }

            if ($this->hasFile('upload_feature_image') && ! empty($data['selected_feature_image_url'])) {
                $validator->errors()->add(
                    'upload_feature_image',
                    __("form-requests.news.upload_feature_image.select_one")
                );

                $validator->errors()->add(
                    'selected_feature_image_url',
                    __("form-requests.news.selected_feature_image_url.select_one")
                );
            }

            if (! empty($data['selected_feature_image_url'])) {
                if ($this->mediaFeatureImageDimensionInvalid($data['selected_feature_image_url'])) {
                    $validator->errors()->add(
                        "selected_feature_image_url",
                        __("form-requests.news.selected_feature_image_url.dimensions")
                    );
                }
            }

            if ($this->hasFile('upload_feature_image_mobile') && ! empty($data['selected_feature_image_mobile_url'])) {
                $validator->errors()->add(
                    'upload_feature_image_mobile',
                    __("form-requests.news.upload_feature_image_mobile.select_one")
                );

                $validator->errors()->add(
                    'selected_feature_image_mobile_url',
                    __("form-requests.news.upload_feature_image_mobile.select_one")
                );
            }

            if (! empty($data['selected_feature_image_mobile_url'])) {
                if ($this->mediaFeatureImageMobileDimensionInvalid($data['selected_feature_image_mobile_url'])) {
                    $validator->errors()->add(
                        "selected_feature_image_mobile_url",
                        __("form-requests.news.selected_feature_image_mobile_url.dimensions")
                    );
                }
            }
        });
    }

    private function normalizeIds(mixed $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        if (! is_array($ids)) {
            return [];
        }

        return array_values(array_filter($ids, function ($id) {
            return ! empty($id);
        }));
    }

    private function mediaFeatureImageDimensionInvalid(string $imageUrl): bool
    {
        $dimensions = @getimagesize($imageUrl);

        if (! $dimensions) {
            return true;
        }

        [$imgWidth, $imgHeight] = $dimensions;

        if (! $imgWidth || ! $imgHeight) {
            return true;
        }

        $aspectRatio   = $imgWidth / $imgHeight;
        $expectedRatio = 16 / 9;

        $ratioInvalid = abs($aspectRatio - $expectedRatio) > 0.01;
        $sizeInvalid  = $imgWidth < 1280 || $imgHeight < 720;

        return $ratioInvalid || $sizeInvalid;
    }

    private function mediaFeatureImageMobileDimensionInvalid(string $imageUrl): bool
    {
        $dimensions = @getimagesize($imageUrl);

        if (! $dimensions) {
            return true;
        }

        [$imgWidth, $imgHeight] = $dimensions;

        if (! $imgWidth || ! $imgHeight) {
            return true;
        }

        $aspectRatio   = $imgWidth / $imgHeight;
        $expectedRatio = 16 / 9;

        $ratioInvalid = abs($aspectRatio - $expectedRatio) > 0.01;
        $sizeInvalid  = $imgWidth < 400 || $imgHeight < 225;

        return $ratioInvalid || $sizeInvalid;
    }
}
