<?php
namespace App\Http\Requests;

use App\Helpers\NewsHelper;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\News;
use App\Models\NewsType;
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
            "news_type_id"                      => ["required"],
            "language_id"                       => ["required"],
            "category_id"                       => ["required"],
            "event_id"                          => ["nullable"],
            "location_id"                       => ["nullable"],

            "title"                             => ["required"],
            "brief"                             => ["required"],
            "sub_title"                         => ["nullable"],
            "content_shoulder"                  => ["nullable"],
            "body"                              => ["nullable"],
            "video_url"                         => ["nullable", "url"],
            "gallery_image_ids"                 => ["nullable"],

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

            "editor_media_ids"                  => ["nullable"],

            "relevant_news_ids"                 => ["nullable"],
            "related_news_ids"                  => ["nullable"],
        ];
    }

    public function messages(): array
    {
        return [
            "title.required"                         => __("form-requests.news.title.required"),
            "title.string"                           => __("form-requests.news.title.string"),
            "title.max"                              => __("form-requests.news.title.max"),

            "news_type_id.required"                  => __("form-requests.news.news_type_id.required"),
            "language_id.required"                   => __("form-requests.news.language_id.required"),
            "category_id.required"                   => __("form-requests.news.category_id.required"),

            "video_url.url"                          => __("form-requests.news.video_url.url"),

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

        $isUpdate = $this->route('slug') ? true : false;

        $validator->after(function ($validator) use ($news, $isUpdate) {
            $data = $validator->getData();

            if (! empty($data['title'])) {
                $dates = [
                    now()->format('Y-m-d'),
                    now()->subDay()->format('Y-m-d'),
                ];

                foreach ($dates as $date) {
                    $newsesQuery = News::where('title', $data['title'])
                        ->whereDate('created_at', $date)->where("language_id", $data['language_id']);

                    if ($news) {
                        $newsesQuery->where('id', '!=', $news->id);
                    }

                    if ($newsesQuery->exists()) {
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

            if (! empty($data["news_type_id"]) && ! (NewsType::where("id", $data["news_type_id"])->exists())) {
                $validator->errors()->add(
                    'news_type_id',
                    __("form-requests.news.news_type.not_found")
                );
            }

            $newsType = NewsType::where("id", $data["news_type_id"])->first();
            if ($newsType && ($newsType->name == NewsHelper::NEWS_TYPE_STORY) && empty($data["body"])) {
                $validator->errors()->add(
                    'body',
                    __("form-requests.news.body.required")
                );
            }

            if ($newsType && ($newsType->name == NewsHelper::NEWS_TYPE_VIDEO) && empty($data["video_url"])) {
                $validator->errors()->add(
                    'video_url',
                    __("form-requests.news.video_url.required")
                );
            }

            if ($newsType && ($newsType->name == NewsHelper::NEWS_TYPE_IMAGE_GALLERY) && empty($data["gallery_image_ids"]) && ! $isUpdate) {
                $validator->errors()->add(
                    'gallery_image_ids',
                    __("form-requests.news.gallery_image_ids.required")
                );
            }

            if (! empty($data["tag_ids"])) {
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
            }

            if (! empty($data["contributor_ids"])) {
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
            }

            if (! empty($data["relevant_news_ids"])) {
                $relevantIds = $this->normalizeIds($data['relevant_news_ids'] ?? []);
                foreach ($relevantIds as $relevantId) {
                    if (! News::where('id', $relevantId)->where("is_published", true)->exists()) {
                        $validator->errors()->add(
                            'relevant_news_ids',
                            __("form-requests.news.relevant_news_ids.not_found")
                        );

                        break;
                    }
                }
            }

            if (! empty($data["related_news_ids"])) {
                $relatedIds = $this->normalizeIds($data['related_news_ids'] ?? []);
                foreach ($relatedIds as $relatedId) {
                    if (! News::where('id', $relatedId)->where("is_published", true)->exists()) {
                        $validator->errors()->add(
                            'related_news_ids',
                            __("form-requests.news.related_news_ids.not_found")
                        );

                        break;
                    }
                }
            }

            if (! empty($data["breaking_news_id"])) {
                if (! BreakingNews::where('id', $data["breaking_news_id"])->where("is_published", true)->exists()) {
                    $validator->errors()->add(
                        'breaking_news_id',
                        __("form-requests.news.breaking_news_id.not_found")
                    );
                } else {
                    $extingBreakingNews = BreakingNews::where('id', $data["breaking_news_id"])->where("is_published", true)->first();

                    if ($news) {

                        if (! ($extingBreakingNews->news_id == null) && ! ($extingBreakingNews->news_id == $news->id)) {
                            $validator->errors()->add(
                                'breaking_news_id',
                                __("form-requests.news.breaking_news_id.already_sync_to_news")
                            );
                        }

                    }else{
                        if (! ($extingBreakingNews->news_id == null)) {
                            $validator->errors()->add(
                                'breaking_news_id',
                                __("form-requests.news.breaking_news_id.already_sync_to_news")
                            );
                        }
                    }
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
