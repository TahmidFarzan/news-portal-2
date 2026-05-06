<?php
namespace App\Http\Requests;

use App\Helpers\NewsHelper;
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
            "news_type"                    => ["required"],
            "language_id"                  => ["required"],
            "category_id"                  => ["required"],

            "title"                        => ["required"],
            "brief"                        => ["required"],

            "sub_title"                    => ["nullable"],
            "content_shoulder"             => ["nullable"],

            "event_id"                     => ["nullable"],
            "location_id"                  => ["nullable"],

            "tag_ids"                      => ["nullable"],
            "contributor_ids"              => ["nullable"],

            "seo_title"                    => ["nullable"],
            "seo_brief"                    => ["nullable"],
            "seo_keywords"                 => ["nullable"],

            "upload_feature_image"         => [
                "nullable",
                "image",
                "mimes:jpg,jpeg,png,webp",
                "dimensions:ratio=16/9,width=1280,height=720",
            ],
            "upload_feature_image_caption" => ["nullable", "required_with:upload_feature_image"],
            "selected_feature_image_url"   => ["nullable", "url"],

            "upload_thumbnail"             => [
                "nullable",
                "image",
                "mimes:jpg,jpeg,png,webp",
                "dimensions:ratio=16/9,width=400,height=225",
            ],
            "upload_thumbnail_caption"     => ["nullable", "required_with:upload_thumbnail"],
            "selected_thumbnail_url"       => ["nullable", "url"],

            "body"                         => ["nullable"],
            "video_url"                    => ["nullable", "url"],

            "page_section"                 => ["nullable"],

            "content_media_ids"            => ["nullable"],
        ];
    }

    public function messages(): array
    {
        return [
            "title.required"                             => __("form-requests.news.title.required"),
            "title.string"                               => __("form-requests.news.title.string"),
            "title.max"                                  => __("form-requests.news.title.max"),

            "language_id.required"                       => __("form-requests.news.language_id.required"),
            "category_id.required"                       => __("form-requests.news.category_id.required"),
            "news_type.required"                         => __("form-requests.news.news_type.required"),

            "video_url.url"                              => __("form-requests.news.video_url.url"),

            "upload_feature_image.image"                 => __("form-requests.news.upload_feature_image.image"),
            "upload_feature_image.mimes"                 => __("form-requests.news.upload_feature_image.image"),
            "upload_feature_image.dimensions"            => __("form-requests.news.upload_feature_image.dimensions"),

            "upload_feature_image_caption.required_with" => __("form-requests.news.upload_feature_image_caption.required"),

            "selected_feature_image_url.url"             => __("form-requests.news.selected_feature_image_url.url"),

            "upload_thumbnail.image"                     => __("form-requests.news.upload_thumbnail.image"),
            "upload_thumbnail.mimes"                     => __("form-requests.news.upload_thumbnail.image"),
            "upload_thumbnail.dimensions"                => __("form-requests.news.upload_thumbnail.dimensions"),

            "upload_thumbnail_caption.required_with"     => __("form-requests.news.upload_thumbnail_caption.required"),

            "selected_thumbnail_url.url"                 => __("form-requests.news.selected_thumbnail_url.url"),
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

            if (! empty($data["news_type"])) {
                $allowedNewsTypes = [
                    NewsHelper::NEWS_TYPE_STORY,
                    NewsHelper::NEWS_TYPE_VIDEO,
                    NewsHelper::NEWS_TYPE_PHOTO_GALLERY,
                ];

                if (! in_array($data["news_type"], $allowedNewsTypes, true)) {
                    $validator->errors()->add(
                        'news_type',
                        __("form-requests.news.news_type.not_found")
                    );
                }
            }

            if (! empty($data["news_type"]) && $data["news_type"] === NewsHelper::NEWS_TYPE_STORY) {
                if (empty($data["body"])) {
                    $validator->errors()->add(
                        'body',
                        __("form-requests.news.body.required")
                    );
                }
            }

            if (! empty($data["news_type"]) && $data["news_type"] === NewsHelper::NEWS_TYPE_VIDEO) {
                if (empty($data["video_url"])) {
                    $validator->errors()->add(
                        'video_url',
                        __("form-requests.news.video_url.required")
                    );
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

            if (!$news?->feature_image && !$this->hasFile('upload_feature_image') && !empty($data['selected_feature_image_url'])) {
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

            if ($this->hasFile('upload_thumbnail') && ! empty($data['selected_thumbnail_url'])) {
                $validator->errors()->add(
                    'upload_thumbnail',
                    __("form-requests.news.upload_thumbnail.select_one")
                );

                $validator->errors()->add(
                    'selected_thumbnail_url',
                    __("form-requests.news.upload_thumbnail.select_one")
                );
            }

            if (! empty($data['selected_thumbnail_url'])) {
                if ($this->mediaThumbnailImageDimensionInvalid($data['selected_thumbnail_url'])) {
                    $validator->errors()->add(
                        "selected_thumbnail_url",
                        __("form-requests.news.selected_thumbnail_url.dimensions")
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

    private function mediaThumbnailImageDimensionInvalid(string $imageUrl): bool
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
