<?php
namespace App\Services\BackOffice;

use App\Helpers\MediaHelper;
use App\Helpers\NewsHelper;
use App\Helpers\TagifyHelper;
use App\Http\Requests\NewsRequest;
use App\Jobs\NewsContributorSyncJob;
use App\Jobs\NewsTagSyncJob;
use App\Models\News;
use App\Services\BackOffice\MediaService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    public function loadRelations(News $news): News
    {
        $news->load([
            'createdBy',

            'language',

            'category',

            'event',
            'location',

            'tags'         => fn($query)  => $query->orderBy('tags.created_at', 'desc')->limit(10),
            'tags.trend',

            'contributors'  => fn($query)  => $query->orderBy('contributors.created_at', 'desc')->limit(10),

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

        $query = News::query()->with(["language", "category", "event", "location"]);

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
                    ->orWhere('code', 'like', "%{$search}%");
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

            $news->news_type   = $request->input('news_type', );
            $news->language_id = $request->input('language_id');

            $news->category_id = $request->input('category_id');

            $news->event_id    = $request->input('event_id');
            $news->location_id = $request->input('location_id');

            $news->title     = $request->input('title');
            $news->sub_title = $request->input('sub_title');

            $news->content_shoulder = $request->input('content_shoulder');

            $news->brief = $request->input('brief');

            $news->body      = ($request->input('news_type') == NewsHelper::NEWS_TYPE_STORY) ? $request->input('body') : null;
            $news->video_url = ($request->input('news_type') == NewsHelper::NEWS_TYPE_VIDEO) ? $request->input('video_url') : null;

            $news->page_section = $request->input('page_section');

            $news->seo_title    = $request->input('seo_title') ?? $request->input('title');
            $news->seo_brief    = $request->input('seo_brief') ?? $request->input('brief');
            $news->seo_keywords = TagifyHelper::dataStringFormatFull($request->input('seo_keywords')) ?? null;

            $news->is_published = $request->input('is_published') ? true : false;

            $news->created_by_id = $isNew ? Auth::id() : $news->created_by_id;

            $news->save();

            DB::commit();

            self::featureImageSave($request, $news);
            self::thumbnailImageSave($request, $news);
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

    private function syncAttributesJob(NewsRequest $request, News $news)
    {
        if ($request->input('tag_ids')) {
            NewsTagSyncJob::dispatch($news, $request->input('tag_ids'));
        }

        if ($request->input('contributor_ids')) {
            NewsContributorSyncJob::dispatch($news, $request->input('contributor_ids'));
        }
    }

    private function featureImageSave(NewsRequest $request, News $news)
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
                            "caption" => $request->input('upload_feature_image_caption'),
                            "role"    => MediaHelper::MEDIA_ROLE_NEWS_FEATURE_IMAGE,
                        ]
                    )
                    ->toMediaCollection($news->media_collection_name);
            }
        }

        if ($request->input('media_selected_feature_image_url')) {
            self::deleteExtingFeatureImage($news);
            $mediaFeatureImageUrl       = $request->input('media_selected_feature_image_url');
            $mediaFeatureImageExtension = pathinfo($mediaFeatureImageUrl, PATHINFO_EXTENSION);
            $mediaFeatureImageFileName  = MediaHelper::generateMediaName($news->name, $mediaFeatureImageExtension, 200);

            $news->addMediaFromUrl($mediaFeatureImageUrl)
                ->usingName($news->title)
                ->usingFileName($mediaFeatureImageFileName)
                ->withCustomProperties(
                    [
                        'caption' => $request->input('upload_feature_image_caption'),
                        'alt'     => $news->title,
                        "role"    => MediaHelper::MEDIA_ROLE_NEWS_FEATURE_IMAGE,
                    ]
                )
                ->toMediaCollection($news->media_collection_name);
        }
    }

    private function thumbnailImageSave(NewsRequest $request, News $news)
    {
        if ($request->hasFile('upload_thumbnail')) {
            self::deleteExtingThumbnail($news);

            $thumbnail = $request->file('upload_thumbnail');

            if ($thumbnail) {
                $extension = $thumbnail->getClientOriginalExtension();
                $fileName  = MediaHelper::generateMediaName($news->title, $extension, 200);

                $news->addMedia($thumbnail)
                    ->usingFileName($fileName)
                    ->usingName($news->title)
                    ->withCustomProperties(
                        [
                            "alt"     => $news->title,
                            "caption" => $news->input('upload_thumbnail_caption'),
                            "role"    => MediaHelper::MEDIA_ROLE_NEWS_THUMBNAIL_IMAGE,
                        ]
                    )
                    ->toMediaCollection($news->media_collection_name);
            }
        }

        if ($request->input('media_selected_thumbnail_url')) {
            self::deleteExtingThumbnail($news);
            $mediaThumbnailUrl       = $request->input('media_selected_thumbnail_url');
            $mediaThumbnailExtension = pathinfo($mediaThumbnailUrl, PATHINFO_EXTENSION);
            $mediaThumbnailFileName  = MediaHelper::generateMediaName($news->name, $mediaThumbnailExtension, 200);

            $news->addMediaFromUrl($mediaThumbnailUrl)
                ->usingName($news->title)
                ->usingFileName($mediaThumbnailFileName)
                ->withCustomProperties(
                    [
                        'caption' => $news->input('upload_thumbnail_caption'),
                        'alt'     => $news->title,
                        "role"    => MediaHelper::MEDIA_ROLE_NEWS_THUMBNAIL_IMAGE,
                    ]
                )
                ->toMediaCollection($news->media_collection_name);
        }
    }

    private function syncContentMedia(NewsRequest $request, News $news): void
    {
        if ($request->filled('body_media_ids')) {
            $contentMediaIds = explode(',', $request->input('body_media_ids'));
            $contentMediaIds = array_filter($contentMediaIds);

            if (count($contentMediaIds)) {
                $this->mediaService->transferSingleMediaByMediaIds($contentMediaIds, $news);
            }
        }
    }

    private static function deleteExtingFeatureImage(News $news)
    {
        $extingFeatureImage = $news->feature_image;
        if ($extingFeatureImage) {
            $extingFeatureImage->delete();
        }
    }

    private static function deleteExtingThumbnail(News $news)
    {
        $extingThumbnail = $news->thumbnail;
        if ($extingThumbnail) {
            $extingThumbnail->delete();
        }
    }
}
