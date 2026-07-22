<?php
namespace App\Services\BackOffice;

use App\Helpers\MediaHelper;
use App\Helpers\NewsHelper;
use App\Helpers\PageHelper;
use App\Helpers\TagifyHelper;
use App\Http\Requests\NewsGalleryImageRequest;
use App\Http\Requests\NewsGalleryImageSequenceUpdateRequest;
use App\Http\Requests\NewsRequest;
use App\Jobs\NewsBreakingNewsSyncJob;
use App\Jobs\NewsContentMediaSyncJob;
use App\Jobs\NewsContributorSyncJob;
use App\Jobs\NewsGalleryImagesSyncJob;
use App\Jobs\NewsNewsPlacementAfterCreateSyncJob;
use App\Jobs\NewsRelatedNewsSyncJob;
use App\Jobs\NewsRelevantNewsSyncJob;
use App\Jobs\NewsTagSyncJob;
use App\Models\Category;
use App\Models\Location;
use App\Models\News;
use App\Models\NewsPlacement;
use App\Models\NewsType;
use App\Services\BackOffice\MediaService;
use App\Services\Cache\NewsCacheService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class NewsService
{
    protected MediaService $mediaService;
    protected NewsCacheService $newsCacheService;

    public function __construct(MediaService $mediaService, NewsCacheService $newsCacheService)
    {
        $this->mediaService     = $mediaService;
        $this->newsCacheService = $newsCacheService;
    }

    public function new (): News
    {
        return new News();
    }

    public function find(string $slug): News
    {
        return News::where('slug', $slug)->firstOrFail();
    }

    public function newsTypefindById(string $id): NewsType
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
            'newsPlacements',

            'breakingNews',

            'relevantNews',
            'relevantNews.category',

            'relatedNews',
            'relatedNews.category',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',

            "featureImage",
            "featureImageMobile",
            "galleryImages",
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

        if ($request->filled('category_id')) {
            $categoryIds = [];

            $category = $this->categoryById($request->input('category_id'));
            array_push($categoryIds, $request->input('category_id'));

            if ($category) {
                foreach ($category->children as $perChildren) {
                    array_push($categoryIds, $perChildren->id);
                }
            }
            $query->whereIn('category_id', $categoryIds);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('event_id')) {
            $query->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('location_id')) {
            $locationIds = [];

            $location = $this->locationById($request->input('location_id'));
            array_push($locationIds, $request->input('location_id'));

            if ($location) {
                foreach ($location->children as $perChildren) {
                    array_push($locationIds, $perChildren->id);
                }
            }
            $query->whereIn('location_id', $locationIds);
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
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
            $search     = $request->input('search');
            $likeSearch = "%{$search}%";

            $query->whereAny([
                'title',
                'sub_title',
                'content_shoulder',
                'brief',
                'seo_brief',
                'seo_title',
                'source',
            ], 'like', $likeSearch);
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(NewsRequest $request, News $news): array
    {
        $isNew       = empty($news->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            $news = DB::transaction(function () use ($request, $news, $isNew) {

                $newsType = $this->newsTypefindById($request->input('news_type_id'));

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
                $news->video_url        = (NewsHelper::NEWS_TYPE_VIDEO == $newsType->name) ? $request->input('video_url') : null;

                $news->writer = (NewsHelper::NEWS_TYPE_STORY == $newsType->name) ? $request->input('writer') : null;
                $news->source = (NewsHelper::NEWS_TYPE_STORY == $newsType->name) ? $request->input('source') : null;

                $news->seo_title    = $request->input('seo_title') ?? $request->input('title');
                $news->seo_brief    = $request->input('seo_brief') ?? $request->input('brief');
                $news->seo_keywords = TagifyHelper::dataStringFormatFull($request->input('seo_keywords')) ?? null;

                $news->is_published = $request->input('is_published') ? true : false;

                $news->created_by_id = $isNew ? Auth::id() : $news->created_by_id;

                $news->save();

                $this->featureImageSave($request, $news);
                $this->featureImageMobileSave($request, $news);

                if (! $isNew) {
                    $this->syncMediaAccrodingNewsTypeChangeOnNewsUpdate($news);
                }

                return $news;
            });

            $this->syncAttributesJob($request, $news);
            $this->syncRelatedOrRelevantNews($request, $news);
            $this->syncBreakingNews($request, $news);

            if (NewsHelper::NEWS_TYPE_STORY == $news->newsType->name) {
                $this->syncContentMedia($request, $news);
            }

            if ($isNew) {
                if (NewsHelper::NEWS_TYPE_IMAGE_GALLERY == $news->newsType->name) {
                    $this->syncGalleryImagesMedia($request, $news);
                }
                $this->syncNewPlacementAfterNewsCreate($news);
            }

            if ($request->boolean("clear_cache", false)) {
                $this->newsCacheService->clearCacheByNews($news);
            }

            return [
                'status'  => 'success',
                'message' => __("status-messages.news.{$statusEvent}.success"),
                'data' => [
                    'news_slug' => $news->slug,
                ],
            ];
        } catch (Exception $exception) {

            Log::error("Failed to {$statusEvent} news.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.save.failed'),
                'data'    => [
                    'news_slug' => $news->slug,
                ],
            ];
        }
    }

    public function delete(News $news): array
    {

        try {
            DB::transaction(function () use ($news) {
                $news->is_published = false;
                $news->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.delete.success'),
            ];
        } catch (Exception $exception) {

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

        try {

            DB::transaction(function () use ($news) {
                $news->is_published = true;
                $news->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.restore.success'),
            ];
        } catch (Exception $exception) {

            Log::error('News restore failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.restore.failed'),
            ];
        }
    }

    public function galleryImageFind(News $news, string $mediaSlug): Media
    {
        return $news->getMedia($news->media_collection_name)->where("slug", $mediaSlug)->firstOrFail();
    }

    public function galleryImageSave(NewsGalleryImageRequest $request, News $news): array
    {

        try {

            DB::transaction(function () use ($request, $news) {
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
                                    "role"    => MediaHelper::ROLE_NEWS_GALLERY_IMAGE,
                                ]
                            )
                            ->toMediaCollection($news->media_collection_name);

                        $media->order_column = $this->galleryImageCalculateOrderColumn($request, $news, $media);
                        $media->save();
                    }
                }
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.gallery_image.save.success'),
            ];
        } catch (Exception $exception) {

            Log::error('News image gallery save failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.gallery_image.save.failed'),
            ];
        }
    }

    public function galleryImageUpdate(NewsGalleryImageRequest $request, News $news, Media $media): array
    {

        try {

            DB::transaction(function () use ($request, $news, $media) {
                $media->order_column = $this->galleryImageCalculateOrderColumn($request, $news, $media);

                $media->setCustomProperty(
                    'caption',
                    $request->input('caption', $media->getCustomProperty('caption'))
                );

                $media->setCustomProperty(
                    'alt',
                    $request->input('alt', $media->getCustomProperty('alt'))
                );

                $media->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.gallery_image.update.success'),
            ];
        } catch (Exception $exception) {

            Log::error('News image gallery update failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.gallery_image.update.failed'),
            ];
        }
    }

    public function galleryImageUpdateSequence(News $news, NewsGalleryImageSequenceUpdateRequest $request): array
    {

        try {

            DB::transaction(function () use ($request, $news) {
                $sequence = $request->input("sequence");

                $mediaRoleParameters = [
                    'role' => MediaHelper::ROLE_NEWS_GALLERY_IMAGE,
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
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.gallery_image.sequence.update.success'),
            ];
        } catch (Exception $exception) {

            Log::error('News gallery image sequence update failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.gallery_image.sequence.update.failed'),
            ];
        }
    }

    public function galleryImageDelete(News $news, Media $media): array
    {

        try {

            DB::transaction(function () use ($news, $media) {
                $media->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.gallery_image.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('News image gallery delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.gallery_image.delete.failed'),
            ];
        }
    }

    public function newsPlacementFind(string $newsPlacementSlug): NewsPlacement
    {
        return NewsPlacement::where("slug", $newsPlacementSlug)->firstOrFail();
    }

    public function newsPlacementLoadRelations(NewsPlacement $newsPlacement): NewsPlacement
    {
        $newsPlacement->load([
            'createdBy',

            'news',
            'category',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $newsPlacement;
    }

    public function newsPlacementHomeLead()
    {
        $newsPlacement = NewsPlacement::query()->with("news")
            ->where('page', PageHelper::PAGE_HOME)
            ->where('page_section', PageHelper::PAGE_SECTION_LEAD_NEWS)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return $newsPlacement;
    }

    public function newsPlacementHomeCategory(int | string $categoryId)
    {
        // $page        = PageHelper::PAGE_HOME;
        // $pageSection = PageHelper::PAGE_SECTION_CATEGORY_NEWS;
        // $news = News::query()
        //     ->whereHas('newsPlacements', function ($query) use ($page, $pageSection, $categoryId) {
        //         $query->where('page', $page)
        //             ->where('page_section', $pageSection)
        //             ->where("category_id", $categoryId);
        //     })
        //     ->withMin(['newsPlacements as placement_position' => function ($query) use ($page, $pageSection, $categoryId) {
        //         $query->where('page', $page)
        //             ->where('page_section', $pageSection)
        //             ->where("category_id", $categoryId);
        //     }], 'position')
        //     ->orderBy('placement_position', 'asc')
        //     ->orderBy('id', 'desc')
        //     ->limit(10)
        //     ->get();

        $newsPlacements = NewsPlacement::query()->with("news")
            ->where('page', PageHelper::PAGE_HOME)
            ->where('page_section', PageHelper::PAGE_SECTION_CATEGORY_NEWS)
            ->where("category_id", $categoryId)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return $newsPlacements;
    }

    public function newsPlacementCategoryLead(int | string $categoryId)
    {
        $newsPlacement = NewsPlacement::query()->with("news")
            ->where('page', PageHelper::PAGE_CATEGORY)
            ->where('page_section', PageHelper::PAGE_SECTION_LEAD_NEWS)
            ->where("category_id", $categoryId)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get();

        return $newsPlacement;
    }

    public function newsPlacementGenerateForNews(News $news): array
    {
        try {

            DB::transaction(function () use ($news) {
                $pageHome                = PageHelper::PAGE_HOME;
                $pageCategory            = PageHelper::PAGE_CATEGORY;
                $pageSectionLeadNews     = PageHelper::PAGE_SECTION_LEAD_NEWS;
                $pageSectionCategoryNews = PageHelper::PAGE_SECTION_CATEGORY_NEWS;

                $this->newsPlacementGenerate($pageHome, $pageSectionLeadNews);

                $this->newsPlacementGenerate($pageHome, $pageSectionCategoryNews, $news->category_id);

                $this->newsPlacementGenerate($pageCategory, $pageSectionLeadNews, $news->category_id);
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.news_placement.generate.success'),
            ];
        } catch (Exception $exception) {

            Log::error('News placement generate failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.news_placement.generate.failed'),
            ];
        }
    }

    public function newsPlacementUpdateForNews(Request $request, News $news): array
    {
        try {

            DB::transaction(function () use ($request, $news) {
                $pageHome                = PageHelper::PAGE_HOME;
                $pageCategory            = PageHelper::PAGE_CATEGORY;
                $pageSectionLeadNews     = PageHelper::PAGE_SECTION_LEAD_NEWS;
                $pageSectionCategoryNews = PageHelper::PAGE_SECTION_CATEGORY_NEWS;

                if ($request->filled('home_lead_news_ids_sequence')) {
                    $this->newsPlacementUpdate(
                        $request->input('home_lead_news_ids_sequence'),
                        $pageHome,
                        $pageSectionLeadNews
                    );
                }

                if ($request->filled('home_category_news_ids_sequence')) {
                    $homeCategoryNewsIdsSequence = $request->input('home_category_news_ids_sequence');
                    if (count($request->input('home_category_news_ids_sequence')) < 10) {
                        $homeCategoryNewsIdsSequence = array_merge($request->input('home_lead_news_ids_sequence'), $request->input('home_category_news_ids_sequence'));
                    }
                    $this->newsPlacementUpdate(
                        $homeCategoryNewsIdsSequence,
                        $pageHome,
                        $pageSectionCategoryNews,
                        $news->category_id
                    );
                }

                if ($request->filled('category_lead_news_ids_sequence')) {

                    $categoryLeadNewsIdsSequence = $request->input('category_lead_news_ids_sequence');
                    if (count($request->input('category_lead_news_ids_sequence')) < 10) {
                        $categoryLeadNewsIdsSequence = array_merge($request->input('home_lead_news_ids_sequence'), $request->input('category_lead_news_ids_sequence'));
                    }

                    $this->newsPlacementUpdate(
                        $categoryLeadNewsIdsSequence,
                        $pageCategory,
                        $pageSectionLeadNews,
                        $news->category_id
                    );
                }
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.news_placement.update.success'),
            ];
        } catch (Exception $exception) {

            Log::error('News placement update failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.news_placement.update.failed'),
            ];
        }
    }

    public function newsPlacementDelete(News $news, NewsPlacement $newsPlacement): array
    {
        try {

            DB::transaction(function () use ($news, $newsPlacement) {
                $page        = $newsPlacement->page;
                $pageSection = $newsPlacement->page_section;
                $categoryId  = $newsPlacement->category_id;

                $newsPlacement->delete();

                $this->newsPlacementPositionSyncUpdate($page, $pageSection, $categoryId);
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.news.news_placement.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('News placement delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.news.news_placement.delete.failed'),
            ];
        }
    }

    private function syncAttributesJob(NewsRequest $request, News $news): void
    {
        if ($request->has('tag_ids')) {
            $tagIds = collect($request->input('tag_ids', []))
                ->map(fn($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $existingTagIds = $news->tags()
                ->pluck('tags.id')
                ->map(fn($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($tagIds !== $existingTagIds) {
                NewsTagSyncJob::dispatch($news, $tagIds);
            }
        }

        if ($request->has('contributor_ids')) {
            $contributorIds = collect($request->input('contributor_ids', []))
                ->map(fn($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            $existingContributorIds = $news->contributors()
                ->pluck('contributors.id')
                ->map(fn($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($contributorIds !== $existingContributorIds) {
                NewsContributorSyncJob::dispatch($news, $contributorIds);
            }
        }
    }

    private function syncRelatedOrRelevantNews(NewsRequest $request, News $news): void
    {
        $newsTable = $news->getTable();

        if ($request->has('relevant_news_ids')) {
            $relevantNewsIds = collect($request->input('relevant_news_ids', []))
                ->map(fn($id) => (int) $id)
                ->reject(fn($id) => $id === (int) $news->id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $existingRelevantNewsIds = $news->relevantNews()
                ->pluck("{$newsTable}.id")
                ->map(fn($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($relevantNewsIds !== $existingRelevantNewsIds) {
                NewsRelevantNewsSyncJob::dispatch($news, $relevantNewsIds);
            }
        }

        if ($request->has('related_news_ids')) {
            $relatedNewsIds = collect($request->input('related_news_ids', []))
                ->map(fn($id) => (int) $id)
                ->reject(fn($id) => $id === (int) $news->id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $existingRelatedNewsIds = $news->relatedNews()
                ->pluck("{$newsTable}.id")
                ->map(fn($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all();

            if ($relatedNewsIds !== $existingRelatedNewsIds) {
                NewsRelatedNewsSyncJob::dispatch($news, $relatedNewsIds);
            }
        }
    }

    private function syncBreakingNews(NewsRequest $request, News $news)
    {
        if ($request->filled('breaking_news_id')) {
            if (! ($news->breakingNews?->news_id == $news->id)) {
                NewsBreakingNewsSyncJob::dispatchSync($news, $request->input('breaking_news_id'));
            }

        } else {
            NewsBreakingNewsSyncJob::dispatchSync($news, null);
        }
    }

    private function syncContentMedia(NewsRequest $request, News $news): void
    {
        if ($request->filled('editor_media_ids')) {
            NewsContentMediaSyncJob::dispatchSync($news, $request->input('editor_media_ids'));
        }
    }

    private function syncGalleryImagesMedia(NewsRequest $request, News $news): void
    {
        if ($request->filled('gallery_image_ids')) {
            NewsGalleryImagesSyncJob::dispatchSync($news, $request->input('gallery_image_ids'));
        }
    }

    private function syncMediaAccrodingNewsTypeChangeOnNewsUpdate(News $news): void
    {
        $collectionName = $news->media_collection_name;

        $newsGalleryImageMediaRoleParameters = ["role" => MediaHelper::ROLE_NEWS_GALLERY_IMAGE];
        $newsContentMediaRoleParameters      = ["role" => MediaHelper::ROLE_NEWS_CONTENT_IMAGE];

        if ($news->newsType->name == NewsHelper::NEWS_TYPE_STORY) {
            if ($news->hasMedia($collectionName, $newsGalleryImageMediaRoleParameters)) {
                $news->getMedia($collectionName, $newsGalleryImageMediaRoleParameters)->delete();
            }
        }

        if ($news->newsType->name == NewsHelper::NEWS_TYPE_VIDEO) {
            if ($news->hasMedia($collectionName, $newsGalleryImageMediaRoleParameters)) {
                $news->getMedia($collectionName, $newsGalleryImageMediaRoleParameters)->delete();
            }

            if ($news->hasMedia($collectionName, $newsContentMediaRoleParameters)) {
                $news->getMedia($collectionName, $newsContentMediaRoleParameters)->delete();
            }
        }

        if ($news->newsType->name == NewsHelper::NEWS_TYPE_IMAGE_GALLERY) {
            if ($news->hasMedia($collectionName, $newsContentMediaRoleParameters)) {
                $news->getMedia($collectionName, $newsContentMediaRoleParameters)->delete();
            }
        }
    }

    private function syncNewPlacementAfterNewsCreate(News $news): void
    {
        NewsNewsPlacementAfterCreateSyncJob::dispatchSync($news);
    }

    private function featureImageSave(NewsRequest $request, News $news): void
    {
        if ($request->hasFile('upload_feature_image')) {
            $this->featureImageDeleteExting($news);

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
                            "role"    => MediaHelper::ROLE_NEWS_FEATURE_IMAGE,
                        ]
                    )
                    ->toMediaCollection($news->media_collection_name);
            }
        }

        if ($request->input('selected_feature_image_url')) {
            $this->featureImageDeleteExting($news);
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
                        "role"    => MediaHelper::ROLE_NEWS_FEATURE_IMAGE,
                    ]
                )
                ->toMediaCollection($news->media_collection_name);
        }
    }

    private function featureImageMobileSave(NewsRequest $request, News $news): void
    {
        if ($request->hasFile('upload_feature_image_mobile')) {
            $this->featureImageDeleteExtingMobile($news);

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
                            "role"    => MediaHelper::ROLE_NEWS_FEATURE_IMAGE_MOBILE,
                        ]
                    )
                    ->toMediaCollection($news->media_collection_name);
            }
        }

        if ($request->input('selected_feature_image_mobile_url')) {
            $this->featureImageDeleteExtingMobile($news);
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
                        "role"    => MediaHelper::ROLE_NEWS_FEATURE_IMAGE_MOBILE,
                    ]
                )
                ->toMediaCollection($news->media_collection_name);
        }
    }

    private function featureImageDeleteExting(News $news): void
    {
        if ($news->featureImage) {
            $news->featureImage?->delete();
        }
    }

    private function featureImageDeleteExtingMobile(News $news): void
    {
        if ($news->featureImageMobile) {
            $news->featureImageMobile?->delete();
        }
    }

    private function galleryImageCalculateOrderColumn(NewsGalleryImageRequest $request, News $news, $media): int
    {
        $mediaRoleParameters = [
            'role' => MediaHelper::ROLE_NEWS_GALLERY_IMAGE,
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

    private function newsPlacementUpdate(array $idsBySequence, string $page, string $pageSection, ?int $categoryId = null): void
    {
        $idsBySequence = collect($idsBySequence)->filter()->unique()->values();

        if ($idsBySequence->isEmpty()) {
            return;
        }

        $baseQuery = NewsPlacement::query()
            ->where('page', $page)
            ->where('page_section', $pageSection)
            ->when($categoryId !== null, fn($query) => $query->where('category_id', $categoryId));

        $remainingLimit = max(25 - $idsBySequence->count(), 0);

        $remainingIds = (clone $baseQuery)
            ->whereNotIn('id', $idsBySequence)
            ->orderBy('position')
            ->orderByDesc('id')
            ->limit($remainingLimit)
            ->pluck('id');

        $keepIds = $idsBySequence
            ->merge($remainingIds)
            ->take(25)
            ->values();

        foreach ($keepIds as $index => $id) {
            NewsPlacement::query()
                ->where('id', $id)
                ->update([
                    'position' => $index + 1,
                ]);
        }

        (clone $baseQuery)
            ->whereNotIn('id', $keepIds)
            ->delete();
    }

    private function newsPlacementPositionSyncUpdate(string $page, string $pageSection, ?int $categoryId = null): void
    {
        $baseQuery = NewsPlacement::query()
            ->where('page', $page)
            ->where('page_section', $pageSection)
            ->when($categoryId !== null, fn($query) => $query->where('category_id', $categoryId));

        $placements = (clone $baseQuery)
            ->orderBy('position')
            ->orderByDesc('id')
            ->limit(25)
            ->get(['id']);

        if ($placements->isEmpty()) {
            return;
        }

        foreach ($placements as $index => $placement) {
            $placement->update([
                'position' => $index + 1,
            ]);
        }

        (clone $baseQuery)
            ->whereNotIn('id', $placements->pluck('id'))
            ->delete();
    }

    private function newsPlacementGenerate(string $page, string $pageSection, ?int $categoryId = null): void
    {
        $newsPlacementExists = NewsPlacement::query()
            ->where('page', $page)
            ->where('page_section', $pageSection)
            ->when($categoryId !== null, function ($query) use ($categoryId) {
                $query->where('category_id', $categoryId);
            })
            ->exists();

        if ($newsPlacementExists) {
            return;
        }
        if (! $newsPlacementExists) {
            $newsItems = News::query()
                ->when($categoryId !== null, function ($query) use ($categoryId) {
                    $query->where('category_id', $categoryId);
                })
                ->orderByDesc('id')
                ->limit(25)
                ->get();

            foreach ($newsItems as $newsItem) {
                $newsPositionExit = NewsPlacement::query()
                    ->where('news_id', $newsItem->id)
                    ->where('page', $page)
                    ->where('page_section', $pageSection)
                    ->when($categoryId !== null, function ($query) use ($categoryId) {
                        $query->where('category_id', $categoryId);
                    })->exists();
                if (! $newsPositionExit) {
                    $nextPosition = NewsPlacement::query()
                        ->where('page', $page)
                        ->where('page_section', $pageSection)
                        ->when($categoryId !== null, function ($query) use ($categoryId) {
                            $query->where('category_id', $categoryId);
                        })->max("position");

                    NewsPlacement::create([
                        'news_id'       => $newsItem->id,
                        'page'          => $page,
                        'page_section'  => $pageSection,
                        'category_id'   => ($categoryId !== null) ? $categoryId : null,
                        'position'      => $nextPosition + 1,
                        'created_by_id' => Auth::id(),
                    ]);
                }

            }

        }
    }

    private function categoryById(string | int $slugOrId): Category
    {
        return Category::with("children")->where("id", $slugOrId)->orWhere("slug", $slugOrId)->first();
    }

    private function locationById(string | int $slugOrId): Location
    {
        return Location::with("children")->where("id", $slugOrId)->orWhere("slug", $slugOrId)->first();
    }
}
