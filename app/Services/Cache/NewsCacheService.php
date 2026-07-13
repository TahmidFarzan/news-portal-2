<?php
namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\BreakingNews;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\News;
use App\Models\NewsPlacement;
use App\Models\NewsType;
use App\Models\Tag;
use App\Services\Cache\CategoryCacheService;
use App\Services\Cache\LocationCacheService;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;

class NewsCacheService
{
    private int $cachedTTLOneDay   = 86400;
    private int $cachedTTLThreeMin = 300;

    private string $mainTag   = CacheHelper::TAG_NEWS;
    private string $secondKey = CacheHelper::KEY_NEWS;

    private int $perPage = 5000;
    private int $limit   = 1000;

    public CategoryCacheService $categoryCacheService;
    public LocationCacheService $locationCacheService;

    public function __construct(CategoryCacheService $categoryCacheService, LocationCacheService $locationCacheService)
    {
        $this->categoryCacheService = $categoryCacheService;
        $this->locationCacheService = $locationCacheService;
    }

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_PAGE]);
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_SITEMAP]);
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_FEED]);
    }

    private function perPage(int | null $perPage = null): int
    {
        return $perPage ?? $this->perPage;
    }

    private function dbRecordsGeneralQuery(?Language $language = null): Builder
    {
        $records = News::where('is_published', true);
        if ($language && $language?->id) {
            $records = $records->where('language_id', $language->id);
        }
        return $records;
    }

    private function dbRecords(?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?Language $language = null, int | null $perPage = null, bool $isCursorPaginate = false): LengthAwarePaginator | CursorPaginator
    {
        $request ??= request();
        $sPerPage = $this->perPage($request->input("per_page", $perPage));

        $records = News::with([
            'newsType',
            'language',
            'category',
            'tags',
            'contributors',
            'event',
            'location',
            'featureImage',
            'featureImageMobile',
        ])->where('is_published', true);

        if (
            ($filterModel instanceof NewsType && $filterModel->id) ||
            $request->filled('news_type_id')
        ) {
            $newsTypeId = $filterModel instanceof NewsType
                ? $filterModel->id
                : $request->input('news_type_id');

            $records = $records->where('news_type_id', $newsTypeId);
        }

        if (
            ($filterModel instanceof Category && $filterModel->id) ||
            $request->filled('category_id')
        ) {
            $categoryId = $filterModel instanceof Category
                ? $filterModel->id
                : $request->input('category_id');

            $categoryIds = [$categoryId];

            $category = $this->categoryCacheService->getRecordById(
                $this->secondKey,
                $categoryId,
                $language,
                $cachedTTL ?? $this->cachedTTLOneDay
            );

            if ($category) {
                foreach ($category->children as $child) {
                    $categoryIds[] = $child->id;
                }
            }

            $records = $records->whereIn('category_id', $categoryIds);
        }

        if (
            ($filterModel instanceof Event && $filterModel->id) ||
            $request->filled('event_id')
        ) {
            $eventId = $filterModel instanceof Event
                ? $filterModel->id
                : $request->input('event_id');

            $records = $records->where('event_id', $eventId);
        }

        if (
            ($filterModel instanceof Location && $filterModel->id) ||
            $request->filled('location_id')
        ) {
            $locationId = $filterModel instanceof Location
                ? $filterModel->id
                : $request->input('location_id');

            $locationIds = [$locationId];

            $location = $this->locationCacheService->getRecordById(
                $this->secondKey,
                $locationId,
                $language,
                $cachedTTL ?? $this->cachedTTLOneDay
            );

            if ($location) {
                foreach ($location->children as $child) {
                    $locationIds[] = $child->id;
                }
            }

            $records = $records->whereIn('location_id', $locationIds);
        }

        if (
            ($filterModel instanceof Tag && $filterModel->id) ||
            $request->filled('tag_id')
        ) {
            $tagId = $filterModel instanceof Tag
                ? $filterModel->id
                : $request->input('tag_id');

            $records = $records->whereHas('tags', function (Builder $query) use ($tagId) {
                $query->where('id', $tagId);
            });
        }

        if (
            ($filterModel instanceof Contributor && $filterModel->id) ||
            $request->filled('contributor_id')
        ) {
            $contributorId = $filterModel instanceof Contributor
                ? $filterModel->id
                : $request->input('contributor_id');

            $records = $records->whereHas('contributors', function (Builder $query) use ($contributorId) {
                $query->where('id', $contributorId);
            });
        }

        if ($language?->id) {
            $records = $records->where('language_id', $language->id);
        }

        $records = $records
            ->orderByDesc('id')
            ->orderByDesc('created_at');

        if ($isCursorPaginate) {
            return $records
                ->cursorPaginate($sPerPage)
                ->withQueryString();
        }

        return $records
            ->paginate($sPerPage);
    }

    private function dbRecordsLimit(?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?Language $language = null, int $limit = 4): EloquentCollection
    {
        $request ??= request();

        $records = News::with(['newsType','language',"category", "tags", "contributors", "event", "location", "featureImage", "featureImageMobile"])
            ->where('is_published', true);

        if ($language && $language?->id) {
            $records = $records->where('language_id', $language?->id);
        }
        if (
            ($filterModel instanceof NewsType && $filterModel->id) ||
            $request->filled('news_type_id')
        ) {
            $newsTypeId = $filterModel instanceof NewsType
                ? $filterModel->id
                : $request->input('news_type_id');

            $records = $records->where('news_type_id', $newsTypeId);
        }

        if (
            ($filterModel instanceof Category && $filterModel->id) ||
            $request->filled('category_id')
        ) {
            $categoryId = $filterModel instanceof Category
                ? $filterModel->id
                : $request->input('category_id');

            $categoryIds = [$categoryId];

            $category = $this->categoryCacheService->getRecordById(
                $this->secondKey,
                $categoryId,
                $language,
                $cachedTTL ?? $this->cachedTTLOneDay
            );

            if ($category) {
                foreach ($category->children as $child) {
                    $categoryIds[] = $child->id;
                }
            }

            $records = $records->whereIn('category_id', $categoryIds);
        }

        if (
            ($filterModel instanceof Event && $filterModel->id) ||
            $request->filled('event_id')
        ) {
            $eventId = $filterModel instanceof Event
                ? $filterModel->id
                : $request->input('event_id');

            $records = $records->where('event_id', $eventId);
        }

        if (
            ($filterModel instanceof Location && $filterModel->id) ||
            $request->filled('location_id')
        ) {
            $locationId = $filterModel instanceof Location
                ? $filterModel->id
                : $request->input('location_id');

            $locationIds = [$locationId];

            $location = $this->locationCacheService->getRecordById(
                $this->secondKey,
                $locationId,
                $language,
                $cachedTTL ?? $this->cachedTTLOneDay
            );

            if ($location) {
                foreach ($location->children as $child) {
                    $locationIds[] = $child->id;
                }
            }

            $records = $records->whereIn('location_id', $locationIds);
        }

        if (
            ($filterModel instanceof Tag && $filterModel->id) ||
            $request->filled('tag_id')
        ) {
            $tagId = $filterModel instanceof Tag
                ? $filterModel->id
                : $request->input('tag_id');

            $records = $records->whereHas('tags', function (Builder $query) use ($tagId) {
                $query->where('id', $tagId);
            });
        }

        if (
            ($filterModel instanceof Contributor && $filterModel->id) ||
            $request->filled('contributor_id')
        ) {
            $contributorId = $filterModel instanceof Contributor
                ? $filterModel->id
                : $request->input('contributor_id');

            $records = $records->whereHas('contributors', function (Builder $query) use ($contributorId) {
                $query->where('id', $contributorId);
            });
        }

        $records = $records->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
        return $records;
    }

    private function dbRecordsLimitAccrodingNewsPlacement(string | null $pageName = null, string | null $pageSection = null, $category = null, ?Language $language = null, int $limit = 4): EloquentCollection
    {
        $newsPlacementTable = (new NewsPlacement())->getTable();

        $records = News::with([
            'newsType',
            "language",
            'category',

            'event',
            'location',

            'tags',
            'tags.trend',

            'contributors',

            "featureImage",
            "featureImageMobile",
        ]);

        if ($language && $language?->id) {
            $records = $records->where('language_id', $language?->id);
        }

        $records = $records->withWhereHas('newsPlacements', function ($query) use ($category, $pageName, $pageSection) {
            $query->where('category_id', $category?->id)
                ->where('page', $pageName)
                ->where('page_section', $pageSection);
        });

        $records = $records->orderBy(
            NewsPlacement::query()
                ->select('position')
                ->where("language_id", $language->id)
                ->whereColumn("{$newsPlacementTable}.news_id", 'news.id')
                ->where('category_id', $category?->id)
                ->where('page', $pageName)
                ->where('page_section', $pageSection)
                ->limit(1),
            'asc'
        )
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $records;
    }

    private function dbLatest(?int $limit = null, ?Language $language = null, bool $isCursorPaginate = false): EloquentCollection | CursorPaginator
    {
        $limit = $limit ?? $this->limit;

        $records = News::with([
            'newsType',
            "language",
            'category',

            'event',
            'location',

            'tags',
            'tags.trend',

            'contributors',

            "featureImage",
            "featureImageMobile",
        ])->where('is_published', true)
            ->orderBy('id', 'desc');

        if ($language && $language?->id) {
            $records = $records->where('language_id', $language?->id);
        }

        if ($isCursorPaginate) {
            return $records
                ->cursorPaginate($limit)
                ->withQueryString();
        }

        $records = $records->limit($limit)
            ->get();

        return $records;
    }

    private function dbPopuler(?int $limit = null, ?Language $language = null): EloquentCollection
    {
        $limit = $limit ?? $this->limit;

        $records = News::with([
            'newsType',
            "language",
            'category',

            'event',
            'location',

            'tags',
            'tags.trend',

            'contributors',

            "featureImage",
            "featureImageMobile",
        ])->where('is_published', true)
            ->whereBetween('created_at', [
                now()->subDays(7),
                now(),
            ])
            ->where("hit_count", ">", 0);

        if ($language && $language?->id) {
            $records = $records->where('language_id', $language?->id);
        }

        $records = $records
            ->orderByDesc('hit_count')
            ->orderByDesc('id')
            ->orderByDesc('created_at');

        $records = $records
            ->limit($limit)
            ->get();

        return $records;
    }

    private function dbLastPageNo(?Language $language = null, int | null $perPage = null): int
    {
        $perPage = $this->perPage($perPage);

        return (int) ceil($this->dbRecordsGeneralQuery($language)->count() / $perPage);
    }

    private function dbLastPageNoFilter(?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?Language $language = null, int | null $perPage = null): int
    {

        $request ??= request();
        $perPage = $this->perPage($request->input("per_page", $perPage));

        $records = $this->dbRecordsGeneralQuery($language);

        if (
            ($filterModel instanceof NewsType && $filterModel->id) ||
            $request->filled('news_type_id')
        ) {
            $newsTypeId = $filterModel instanceof NewsType
                ? $filterModel->id
                : $request->input('news_type_id');

            $records->where('news_type_id', $newsTypeId);
        }

        if (
            ($filterModel instanceof Category && $filterModel->id) ||
            $request->filled('category_id')
        ) {
            $categoryId = $filterModel instanceof Category
                ? $filterModel->id
                : $request->input('category_id');

            $categoryIds = [$categoryId];

            $category = $this->categoryCacheService->getRecordById(
                $this->secondKey,
                $categoryId,
                $language,
                $cachedTTL ?? $this->cachedTTLOneDay
            );

            if ($category) {
                foreach ($category->children as $child) {
                    $categoryIds[] = $child->id;
                }
            }

            $records->whereIn('category_id', $categoryIds);
        }

        if (
            ($filterModel instanceof Event && $filterModel->id) ||
            $request->filled('event_id')
        ) {
            $eventId = $filterModel instanceof Event
                ? $filterModel->id
                : $request->input('event_id');

            $records->where('event_id', $eventId);
        }

        if (
            ($filterModel instanceof Location && $filterModel->id) ||
            $request->filled('location_id')
        ) {
            $locationId = $filterModel instanceof Location
                ? $filterModel->id
                : $request->input('location_id');

            $locationIds = [$locationId];

            $location = $this->locationCacheService->getRecordById(
                $this->secondKey,
                $locationId,
                $language,
                $cachedTTL ?? $this->cachedTTLOneDay
            );

            if ($location) {
                foreach ($location->children as $child) {
                    $locationIds[] = $child->id;
                }
            }

            $records->whereIn('location_id', $locationIds);
        }

        if (
            ($filterModel instanceof Tag && $filterModel->id) ||
            $request->filled('tag_id')
        ) {
            $tagId = $filterModel instanceof Tag
                ? $filterModel->id
                : $request->input('tag_id');

            $records->whereHas('tags', function (Builder $query) use ($tagId) {
                $query->where('id', $tagId);
            });
        }

        if (
            ($filterModel instanceof Contributor && $filterModel->id) ||
            $request->filled('contributor_id')
        ) {
            $contributorId = $filterModel instanceof Contributor
                ? $filterModel->id
                : $request->input('contributor_id');

            $records->whereHas('contributors', function (Builder $query) use ($contributorId) {
                $query->where('id', $contributorId);
            });
        }

        return (int) ceil($records->count() / $perPage);
    }

    private function dbRecordByIdOrSlug(string | int $idOrSlug, ?Language $language = null): News
    {
        $record = News::with([
            'newsType',
            'language',
            'category',
            "event",
            "tags",
            "location",
            "contributors",
            "featureImage",
            "featureImageMobile",
            "galleryImages",

            'relevantNews' => function ($query) {
                $query->latest('news.created_at')->limit(4);
            },

            'relevantNews.category',

            'relatedNews'  => function ($query) {
                $query->latest('news.created_at')->limit(4);
            },
            'relatedNews.category',
        ]);

        if ($language && $language?->id) {
            $record = $record->where("language_id", $language?->id);
        }

        $record = $record
            ->where("is_published", true)
            ->where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->firstOrFail();
        return $record;
    }

    private function dbBreakingNews(?Request $request = null, ?Language $language = null, int $perPage = 10): CursorPaginator
    {
        $records = BreakingNews::with(
            "news",
            "news.category",
            "news.language",
            "news.newsType",
        );

        if ($language && $language?->id) {
            $records = $records->where("language_id", $language?->id);
            $records = $records->whereRelation('news', 'language_id', $language?->id);
        }

        $records = $records
            ->where('is_published', true)
            ->orderByDesc('created_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage);
        return $records;
    }

    public function getLastPageNo(string $cacheKey, ?Language $language = null, int | null $perPage = null, int | null $cachedTTL = null): int
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForLastPageNo($cacheKey, $this->secondKey, $language);

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $cacheKey]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($language, $perPage);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $cachedTTL ?? $this->cachedTTLOneDay,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return (int) $lastPage;
    }

    public function getLastPageNoByFilter(string $cacheKey, ?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?Language $language = null, int | null $perPage = null, int | null $cachedTTL = null): int
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForLastPageNoByFilter($cacheKey, $this->secondKey, $request, $filterModel, $language);

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $cacheKey]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNoFilter($request, $filterModel, $language, $perPage);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $cachedTTL ?? $this->cachedTTLOneDay,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return (int) $lastPage;
    }

    public function getRecords(string $cacheKey, ?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?Language $language = null, int | null $perPage = null, int | null $cachedTTL = null, bool $isCursorPaginate = false): LengthAwarePaginator | CursorPaginator
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForNews($cacheKey, $this->secondKey, $request, $filterModel, $language, $perPage);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $cacheKey]
        );

        if ($records === null) {
            $records = $this->dbRecords($request, $filterModel, $language, $perPage, $isCursorPaginate);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTLOneDay,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }

    public function getRecordsLimit(string $cacheKey, ?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?Language $language = null, int | null $limit = 4, int | null $cachedTTL = null): SupportCollection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForRecordByLimit($cacheKey, $this->secondKey, $filterModel, $request, $language, $limit);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $cacheKey]
        );

        if ($records === null) {
            $records = $this->dbRecordsLimit($request, $filterModel, $language, $limit);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTLThreeMin,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }

    public function getRecordsLimitAccrodingNewsPlacement(string $cacheKey, string | null $pageName = null, string | null $pageSection = null, $category = null, ?Language $language = null, int $limit = 4, int | null $cachedTTL = null): SupportCollection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForRecordByLimitAccrodingNewsPlacement($cacheKey, $this->secondKey, $pageName, $pageSection, $category, $limit, $language);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $cacheKey]
        );

        if ($records === null) {
            $records = $this->dbRecordsLimitAccrodingNewsPlacement($pageName, $pageSection, $category, $language, $limit);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTLThreeMin,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }

    public function getLatestRecord(string $cacheKey, ?Language $language = null, ?int $limit = null, bool $isCursorPaginate = false, int | null $cachedTTL = null): SupportCollection | CursorPaginator
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForLatest($cacheKey, $this->secondKey, $language, $isCursorPaginate);

        $records = CacheServerHelper::getCachedData($cacheKey);

        if ($records === null) {
            $records = $this->dbLatest($limit, $language, $isCursorPaginate);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTLThreeMin,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }
        return $records;
    }

    public function getPopulerRecord(string $cacheKey, ?Language $language = null, ?int $limit = null, int | null $cachedTTL = null): SupportCollection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForPopuler($cacheKey, $this->secondKey, $language);

        $records = CacheServerHelper::getCachedData($cacheKey);

        if ($records === null) {
            $records = $this->dbPopuler($limit, $language);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTLThreeMin,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }
        return $records;
    }

    public function getRecordBySlug(string $cacheKey, string $slug, ?Language $language = null, int | null $cachedTTL = null): News
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordBySlug($cacheKey, $this->secondKey, $slug, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $cacheKey,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByIdOrSlug($slug, $language);

            CacheServerHelper::cachedData(
                $cacheKey,
                $record,
                $cachedTTL ?? $this->cachedTTLThreeMin,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $record;
    }

    public function getRecordById(string $cacheKey, string $id, ?Language $language = null, int | null $cachedTTL = null): News
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordById($cacheKey, $this->secondKey, $id, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $cacheKey,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByIdOrSlug($id, $language);

            CacheServerHelper::cachedData(
                $cacheKey,
                $record,
                $cachedTTL ?? $this->cachedTTLThreeMin,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $record;
    }

    public function getBreakingNews(string $cacheKey, ?Request $request = null, ?Language $language = null, int $limit = 10, int | null $cachedTTL = null): CursorPaginator
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForBreakingNews($cacheKey, $this->secondKey, $request, $language, $limit);

        $records = CacheServerHelper::getCachedData($cacheKey);

        if ($records === null) {
            $records = $this->dbBreakingNews($request, $language, $limit);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTLThreeMin,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }
        return $records;
    }

}
