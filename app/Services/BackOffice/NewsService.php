<?php
namespace App\Services\BackOffice;

use App\Helpers\MediaHelper;
use App\Helpers\NewsHelper;
use App\Helpers\TagifyHelper;
use App\Http\Requests\NewsGalleryImageRequest;
use App\Http\Requests\NewsGalleryImageSequenceUpdateRequest;
use App\Http\Requests\NewsRequest;
use App\Jobs\NewsContributorSyncJob;
use App\Jobs\NewsTagSyncJob;
use App\Models\News;
use App\Models\NewsType;
use App\Services\BackOffice\MediaService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class NewsService
{
    protected MediaService $mediaService;

    public function __construct(MediaService $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    public function new (): News
    {
        return new News();
    }

    public function find(string $slug): News
    {
        return News::where('slug', $slug)->firstOrFail();
    }

    public function findMedia(News $news, string $mediaSlug): Media
    {
        return $news->getMedia($news->media_collection_name)->where("slug", $mediaSlug)->firstOrFail();
    }

    public function findNewsTypeById(string $id): NewsType
    {
        return NewsType::where('id', $id)->firstOrFail();
    }

    public function loadRelations(News $news): News
    {
        $news->load([
            'createdBy',

            'language',
            'newsType',

            'category',

            'event',
            'location',

            'tags',
            'tags.trend',

            'contributors',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $news;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = News::query()->with(["newsType", "language", "category", "event", "location"]);

        if ($request->filled('news_type_id')) {
            $query->where('news_type_id', $request->input('news_type_id'));
        }

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('location_id')) {
            $query->where('location_id', $request->input('location_id'));
        }

        if ($request->filled("tag_id")) {
            $tagId = $request->input('tag_id');
            $query->whereHas('tags', function ($relationQuery) use ($tagId) {
                $relationQuery->where('id', $tagId);
            });
        }

        if ($request->filled("contributor_id")) {
            $contributorId = $request->input('contributor_id');
            $query->whereHas('contributors', function ($relationQuery) use ($contributorId) {
                $relationQuery->where('id', $contributorId);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('brief', 'like', "%{$search}%")
                    ->orWhere('seo_brief', 'like', '%' . $search . '%')
                    ->orWhere('seo_title', 'like', '%' . $search . '%')
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(NewsRequest $request, News $news): array
    {
        DB::beginTransaction();

        try {
            $isNew       = empty($news->id);
            $statusEvent = $isNew ? "save" : "update";

            $newsType = $this->findNewsTypeById($request->input('news_type_id'));

            $news->news_type_id = $request->input('news_type_id');
            $news->language_id  = $request->input('language_id');
            $news->category_id  = $request->input('category_id');
            $news->event_id     = $request->input('event_id');
            $news->location_id  = $request->input('location_id');

            $news->title            = $request->input('title');
            $news->sub_title        = $request->input('sub_title');
            $news->content_shoulder = $request->input('content_shoulder');
            $news->brief            = $request->input('brief');
            $news->body             = (NewsHelper::NEWS_TYPE_STORY == $newsType->name) ? $request->input('body') : null;
            $news->video_url        = (NewsHelper::NEWS_TYPE_STORY == $newsType->name) ? $request->input('video_url') : null;

            $news->writer = (NewsHelper::NEWS_TYPE_STORY == $newsType->name) ? $request->input('writer') : null;
            $news->source = (NewsHelper::NEWS_TYPE_STORY == $newsType->name) ? $request->input('source') : null;

            $news->seo_title    = $request->input('seo_title') ?? $request->input('title');
            $news->seo_brief    = $request->input('seo_brief') ?? $request->input('brief');
            $news->seo_keywords = TagifyHelper::dataStringFormatFull($request->input('seo_keywords')) ?? null;

            $news->is_published = $request->input('is_published') ? true : false;

            $news->created_by_id = $isNew ? Auth::id() : $news->created_by_id;

            $news->save();

            DB::commit();

            self::featureImageSave($request, $news);
            self::featureImageMobileSave($request, $news);
            self::syncContentMedia($request, $news);

            self::syncAttributesJob($request, $news);

            return [
                'status'  => 'success',
                'message' => __("status-messages.news.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error("Failed to {$statusEvent} news.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.save.failed'),
            ];
        }
    }

    public function delete(News $news): array
    {
        DB::beginTransaction();

        try {
            $news->is_published = false;
            $news->save();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.delete.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('News delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.delete.failed'),
            ];
        }
    }

    public function restore(News $news): array
    {
        DB::beginTransaction();

        try {
            $news->is_published = true;
            $news->save();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.restore.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('News restore failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.restore.failed'),
            ];
        }
    }

    public function galleryImageSave(NewsGalleryImageRequest $request, News $news): array
    {
        DB::beginTransaction();

        try {
            if ($request->hasFile('image')) {

                $image = $request->file('image');

                if ($image) {
                    $extension = $image->getClientOriginalExtension();
                    $fileName  = MediaHelper::generateMediaName($news->title, $extension, 200);

                    $media = $news->addMedia($image)
                        ->usingFileName($fileName)
                        ->usingName($news->title)
                        ->withCustomProperties(
                            [
                                "alt"     => $request->input('alt', $news->title),
                                "caption" => $request->input('caption'),
                                "role"    => MediaHelper::MEDIA_ROLE_NEWS_GALLERY_IMAGE,
                            ]
                        )
                        ->toMediaCollection($news->media_collection_name);

                    $media->order_column = $this->calculateGalleryImageOrderColumn($request, $news, $media);
                    $media->save();
                }
            }

            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.gallery_image_save.success'),
            ];
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('News image gallery save failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.gallery_image_save.failed'),
            ];
        }
    }

    public function galleryImageUpdate(NewsGalleryImageRequest $request, News $news, Media $media): array
    {
        DB::beginTransaction();

        try {
            $media->order_column = $this->calculateGalleryImageOrderColumn($request, $news, $media);

            $media->setCustomProperty(
                'caption',
                $request->input('caption', $media->getCustomProperty('caption'))
            );

            $media->setCustomProperty(
                'alt',
                $request->input('alt', $media->getCustomProperty('alt'))
            );

            $media->save();

            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.gallery_image_update.success'),
            ];
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('News image gallery update failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.gallery_image_update.failed'),
            ];
        }
    }

    public function galleryImageUpdateSequence(News $news, NewsGalleryImageSequenceUpdateRequest $request): array
    {
        DB::beginTransaction();

        try {
            $sequence = $request->input("sequence");

            $mediaRoleParameters = [
                'role' => MediaHelper::MEDIA_ROLE_NEWS_GALLERY_IMAGE,
            ];

            $collectionName = $news->media_collection_name;

            $galleryImages = $news->getMedia($collectionName, $mediaRoleParameters)
                ->values();

            $galleryImagesById = $galleryImages->keyBy('id');

            $currentIds = $galleryImages
                ->pluck('id')
                ->map(fn($id) => (int) $id)
                ->sort()
                ->values();

            $sequenceIds = collect($sequence)
                ->map(fn($id) => (int) $id)
                ->values();

            $sortedSequenceIds = $sequenceIds
                ->sort()
                ->values();

            if ($currentIds->count() !== $sequenceIds->count() || $currentIds->toArray() !== $sortedSequenceIds->toArray()) {
                throw new Exception('Invalid gallery image sequence.');
            }

            $sequenceIds->each(function (int $mediaId, int $index) use ($galleryImagesById) {
                $galleryImage = $galleryImagesById->get($mediaId);

                if (! $galleryImage instanceof Media) {
                    throw new Exception('Invalid gallery image id.');
                }

                $orderColumn = $index + 1;

                if ((int) $galleryImage->order_column !== $orderColumn) {
                    $galleryImage->order_column = $orderColumn;
                    $galleryImage->save();
                }
            });

            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.gallery_image_sequence_update.success'),
            ];
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('News gallery image sequence update failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.gallery_image_sequence_update.failed'),
            ];
        }
    }

    public function galleryImageDelete(News $news, Media $media): array
    {
        DB::beginTransaction();

        try {
            $media->delete();
            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.gallery_image_delete.success'),
            ];
        } catch (Exception $exception) {
            DB::rollback();

            Log::error('News image gallery delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.gallery_image_delete.failed'),
            ];
        }
    }

    private function syncAttributesJob(NewsRequest $request, News $news)
    {
        if ($request->input('tag_ids')) {
            NewsTagSyncJob::dispatch($news, $request->input('tag_ids'));
        }

        if ($request->input('contributor_ids')) {
            NewsContributorSyncJob::dispatch($news, $request->input('contributor_ids'));
        }
    }

    private function featureImageSave(NewsRequest $request, News $news): void
    {
        if ($request->hasFile('upload_feature_image')) {
            self::deleteExtingFeatureImage($news);

            $featureImage = $request->file('upload_feature_image');

            if ($featureImage) {
                $extension = $featureImage->getClientOriginalExtension();
                $fileName  = MediaHelper::generateMediaName($news->title, $extension, 200);

                $news->addMedia($featureImage)
                    ->usingFileName($fileName)
                    ->usingName($news->title)
                    ->withCustomProperties(
                        [
                            "alt"     => $news->title,
                            "caption" => $request->input('feature_image_caption'),
                            "role"    => MediaHelper::MEDIA_ROLE_NEWS_FEATURE_IMAGE,
                        ]
                    )
                    ->toMediaCollection($news->media_collection_name);
            }
        }

        if ($request->input('selected_feature_image_url')) {
            self::deleteExtingFeatureImage($news);
            $mediaFeatureImageUrl       = $request->input('selected_feature_image_url');
            $mediaFeatureImageExtension = pathinfo($mediaFeatureImageUrl, PATHINFO_EXTENSION);
            $mediaFeatureImageFileName  = MediaHelper::generateMediaName($news->name, $mediaFeatureImageExtension, 200);

            $news->addMediaFromUrl($mediaFeatureImageUrl)
                ->usingName($news->title)
                ->usingFileName($mediaFeatureImageFileName)
                ->withCustomProperties(
                    [
                        'caption' => $request->input('feature_image_caption'),
                        'alt'     => $news->title,
                        "role"    => MediaHelper::MEDIA_ROLE_NEWS_FEATURE_IMAGE,
                    ]
                )
                ->toMediaCollection($news->media_collection_name);
        }
    }

    private function featureImageMobileSave(NewsRequest $request, News $news): void
    {
        if ($request->hasFile('upload_feature_image_mobile')) {
            self::deleteExtingFeatureImageMobile($news);

            $featureImageMobile = $request->file('upload_feature_image_mobile');

            if ($featureImageMobile) {
                $extension = $featureImageMobile->getClientOriginalExtension();
                $fileName  = MediaHelper::generateMediaName($news->title, $extension, 200);

                $news->addMedia($featureImageMobile)
                    ->usingFileName($fileName)
                    ->usingName($news->title)
                    ->withCustomProperties(
                        [
                            "alt"     => $news->title,
                            "caption" => $news->input('feature_image_caption'),
                            "role"    => MediaHelper::MEDIA_ROLE_NEWS_FEATURE_IMAGE_MOBILE,
                        ]
                    )
                    ->toMediaCollection($news->media_collection_name);
            }
        }

        if ($request->input('selected_feature_image_mobile_url')) {
            self::deleteExtingFeatureImageMobile($news);
            $mediaFeatureImageMobileUrl       = $request->input('selected_feature_image_mobile_url');
            $mediaFeatureImageMobileExtension = pathinfo($mediaFeatureImageMobileUrl, PATHINFO_EXTENSION);
            $mediaFeatureImageMobileFileName  = MediaHelper::generateMediaName($news->name, $mediaFeatureImageMobileExtension, 200);

            $news->addMediaFromUrl($mediaFeatureImageMobileUrl)
                ->usingName($news->title)
                ->usingFileName($mediaFeatureImageMobileFileName)
                ->withCustomProperties(
                    [
                        'caption' => $news->input('upload_feature_image_mobile_caption'),
                        'alt'     => $news->title,
                        "role"    => MediaHelper::MEDIA_ROLE_NEWS_FEATURE_IMAGE_MOBILE,
                    ]
                )
                ->toMediaCollection($news->media_collection_name);
        }
    }

    private function syncContentMedia(NewsRequest $request, News $news): void
    {
        if (! $request->filled('editor_media_ids')) {
            return;
        }

        $contentMediaIds = explode(',', $request->input('editor_media_ids'));
        $contentMediaIds = array_filter($contentMediaIds);

        if (! count($contentMediaIds)) {
            return;
        }

        $replacementPairs = $this->mediaService->copyOrUpdateMediaByMediaIds(
            $contentMediaIds,
            $news,
            MediaHelper::MEDIA_ROLE_NEWS_CONTENT_IMAGE
        );

        if (! $replacementPairs) {
            return;
        }

        $body = $news->body ?? '';

        foreach ($replacementPairs as $replacementPair) {
            if ($replacementPair->old_media_id == $replacementPair->new_media_id) {
                continue;
            }

            $oldMedia = $this->mediaService->firstById($replacementPair->old_media_id);
            $newMedia = $this->mediaService->firstById($replacementPair->new_media_id);

            if (! $oldMedia || ! $newMedia) {
                continue;
            }

            $replaceableUrls = [
                [
                    'old' => $oldMedia->url ?? null,
                    'new' => $newMedia->url ?? null,
                ],
                [
                    'old' => $oldMedia->original_url ?? null,
                    'new' => $newMedia->original_url ?? null,
                ],
                [
                    'old' => $oldMedia->media_url ?? null,
                    'new' => $newMedia->media_url ?? null,
                ],
                [
                    'old' => $oldMedia->media_srcset ?? null,
                    'new' => $newMedia->media_srcset ?? null,
                ],
            ];

            foreach ($replaceableUrls as $replaceableUrl) {
                if (! $replaceableUrl['old'] || ! $replaceableUrl['new']) {
                    continue;
                }

                $body = str_replace(
                    $replaceableUrl['old'],
                    $replaceableUrl['new'],
                    $body
                );
            }
        }

        if ($body !== $news->body) {
            $news->body = $body;
            $news->save();
        }
    }

    private function calculateGalleryImageOrderColumn(NewsGalleryImageRequest $request, News $news, $media): int
    {
        $mediaRoleParameters = [
            'role' => MediaHelper::MEDIA_ROLE_NEWS_GALLERY_IMAGE,
        ];

        $collectionName = $news->media_collection_name;

        $orderColumn = $request->input('order_column');

        if (! $orderColumn) {
            return $media->order_column ?: (((int) $news->getMedia($collectionName, $mediaRoleParameters)->max('order_column')) + 1);
        }

        if ($orderColumn == $media->order_column) {
            return $orderColumn;
        }

        $galleryImages = $news->getMedia($collectionName, $mediaRoleParameters)
            ->sortBy('order_column')
            ->reject(fn($galleryImage) => $galleryImage->id === $media->id)
            ->values();

        $orderColumn = max(1, min($orderColumn, $galleryImages->count() + 1));

        $galleryImages->splice($orderColumn - 1, 0, [$media]);

        $galleryImages->values()->each(function ($galleryImage, $index) {
            $galleryImage->order_column = $index + 1;
            $galleryImage->save();
        });

        return $orderColumn;
    }

    private static function deleteExtingFeatureImage(News $news): void
    {
        $extingFeatureImage = $news->feature_image;
        if ($extingFeatureImage) {
            $extingFeatureImage->delete();
        }
    }

    private static function deleteExtingFeatureImageMobile(News $news): void
    {
        $extingFeatureImageMobile = $news->feature_image_mobile;
        if ($extingFeatureImageMobile) {
            $extingFeatureImageMobile->delete();
        }
    }
}
