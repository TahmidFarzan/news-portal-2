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
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;

class NewsCacheService
{
    private int $perPage = 5000;

    private int $limit = 1000;

    private int $cachedTTL = 86400;

    private string $mainTag = CacheHelper::TAG_NEWS;

    private string $secondKey = CacheHelper::KEY_NEWS;

    private CategoryCacheService $categoryCacheService;

    private LocationCacheService $locationCacheService;

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

    private function perPage(?int $perPage = null): int
    {
        return $perPage ?? $this->perPage;
    }

    private function dbRecordsGeneralQuery(Language $language): Builder
    {
        $records = News::where('is_published', true);
        if ($language && $language?->id) {
            $records = $records->where('language_id', $language->id);
        }

        return $records;
    }

    private function dbRecords(Language $language, ?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?int $perPage = null, bool $isCursorPaginate = false): LengthAwarePaginator | CursorPaginator
    {
        $request ??= request();
        $sPerPage = $this->perPage($request->input('per_page', $perPage));

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
                $language,
                $categoryId,

                $this->cachedTTL
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
                $language,
                $locationId,
                $this->cachedTTL
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

    private function dbRecordsLimit(Language $language, ?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, int $limit = 4): EloquentCollection
    {
        $request ??= request();

        $records = News::with(['newsType', 'language', 'category', 'tags', 'contributors', 'event', 'location', 'featureImage', 'featureImageMobile'])
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
                $language,
                $categoryId,

                $this->cachedTTL
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
                $language,
                $locationId,

                $this->cachedTTL
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

    private function dbRecordsLimitAccrodingNewsPlacement(Language $language, ?string $pageName = null, ?string $pageSection = null, ?Category $category = null, int $limit = 4): EloquentCollection
    {
        $newsPlacementTable = (new NewsPlacement)->getTable();

        $records = News::with([
            'newsType',
            'language',
            'category',

            'event',
            'location',

            'tags',
            'tags.trend',

            'contributors',

            'featureImage',
            'featureImageMobile',
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
                ->where('language_id', $language->id)
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

    private function dbLatest(Language $language, ?int $limit = null, bool $isCursorPaginate = false): EloquentCollection | CursorPaginator
    {
        $limit = $limit ?? $this->limit;

        $records = News::with([
            'newsType',
            'language',
            'category',

            'event',
            'location',

            'tags',
            'tags.trend',

            'contributors',

            'featureImage',
            'featureImageMobile',
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

    private function dbPopuler(Language $language, ?int $limit = null, ): EloquentCollection
    {
        $limit = $limit ?? $this->limit;

        $records = News::with([
            'newsType',
            'language',
            'category',

            'event',
            'location',

            'tags',
            'tags.trend',

            'contributors',

            'featureImage',
            'featureImageMobile',
        ])->where('is_published', true)
            ->whereBetween('created_at', [
                now()->subDays(7),
                now(),
            ])
            ->where('hit_count', '>', 0);

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

    private function dbLastPageNo(Language $language, ?int $perPage = null): int
    {
        $perPage = $this->perPage($perPage);

        return (int) ceil($this->dbRecordsGeneralQuery($language)->count() / $perPage);
    }

    private function dbLastPageNoFilter(Language $language, ?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?int $perPage = null): int
    {

        $request ??= request();
        $perPage = $this->perPage($request->input('per_page', $perPage));

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
                $this->cachedTTL
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
                $this->cachedTTL
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

    private function dbRecordByIdOrSlug(Language $language, string | int $idOrSlug, ): News
    {
        $record = News::with([
            'newsType',
            'language',
            'category',
            'event',
            'tags',
            'location',
            'contributors',
            'featureImage',
            'featureImageMobile',
            'galleryImages',

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
            $record = $record->where('language_id', $language?->id);
        }

        $record = $record
            ->where('is_published', true)
            ->where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->firstOrFail();

        return $record;
    }

    private function dbBreakingNews(Language $language, ?Request $request = null, int $perPage = 10): CursorPaginator
    {
        $records = BreakingNews::with(
            'news',
            'news.category',
            'news.language',
            'news.newsType',
        );

        if ($language && $language?->id) {
            $records = $records->where('language_id', $language?->id);
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

    public function getLastPageNo(string $cacheKey, Language $language, ?int $perPage = null, ?int $cachedTTL = null): int
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
                $cachedTTL ?? $this->cachedTTL,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return (int) $lastPage;
    }

    public function getLastPageNoByFilter(string $cacheKey, Language $language, ?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?int $perPage = null, ?int $cachedTTL = null): int
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForLastPageNoByFilter($cacheKey, $this->secondKey, $request, $filterModel, $language);

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $cacheKey]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNoFilter($language, $request, $filterModel, $perPage);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return (int) $lastPage;
    }

    public function getRecords(string $cacheKey, Language $language, ?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?int $perPage = null, ?int $cachedTTL = null, bool $isCursorPaginate = false): LengthAwarePaginator | CursorPaginator
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForNews($cacheKey, $this->secondKey, $request, $filterModel, $language, $perPage);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $cacheKey]
        );

        if ($records === null) {
            $records = $this->dbRecords($language, $request, $filterModel, $perPage, $isCursorPaginate);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }

    public function getRecordsLimit(string $cacheKey, Language $language, ?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, int $limit = 4, ?int $cachedTTL = null): SupportCollection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForRecordByLimit($cacheKey, $this->secondKey, $filterModel, $request, $language, $limit);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $cacheKey]
        );

        if ($records === null) {
            $records = $this->dbRecordsLimit($language, $request, $filterModel, $limit);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }

    public function getRecordsLimitAccrodingNewsPlacement(string $cacheKey, Language $language, ?string $pageName = null, ?string $pageSection = null, ?Category $category = null, int $limit = 4, ?int $cachedTTL = null): SupportCollection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForRecordByLimitAccrodingNewsPlacement($cacheKey, $this->secondKey, $pageName, $pageSection, $category, $limit, $language);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $cacheKey]
        );

        if ($records === null) {
            $records = $this->dbRecordsLimitAccrodingNewsPlacement($language, $pageName, $pageSection, $category, $limit);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }

    public function getLatestRecord(string $cacheKey, Language $language, ?int $limit = null, bool $isCursorPaginate = false, ?int $cachedTTL = null): SupportCollection | CursorPaginator
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForLatest($cacheKey, $this->secondKey, $language, $isCursorPaginate);

        $records = CacheServerHelper::getCachedData($cacheKey);

        if ($records === null) {
            $records = $this->dbLatest($language, $limit, $isCursorPaginate);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }

    public function getPopulerRecord(string $cacheKey, Language $language, ?int $limit = null, ?int $cachedTTL = null): SupportCollection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForPopuler($cacheKey, $this->secondKey, $language);

        $records = CacheServerHelper::getCachedData($cacheKey);

        if ($records === null) {
            $records = $this->dbPopuler($language, $limit, );

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }

    public function getRecordBySlug(string $cacheKey, Language $language, string $slug, ?int $cachedTTL = null): News
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
            $record = $this->dbRecordByIdOrSlug($language, $slug, );

            CacheServerHelper::cachedData(
                $cacheKey,
                $record,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $record;
    }

    public function getRecordById(string $cacheKey, Language $language, string $id, ?int $cachedTTL = null): News
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
            $record = $this->dbRecordByIdOrSlug($language, $id, );

            CacheServerHelper::cachedData(
                $cacheKey,
                $record,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $record;
    }

    public function getBreakingNews(string $cacheKey, Language $language, ?Request $request = null, int $limit = 10, ?int $cachedTTL = null): CursorPaginator
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForBreakingNews($cacheKey, $this->secondKey, $request, $language, $limit);

        $records = CacheServerHelper::getCachedData($cacheKey);

        if ($records === null) {
            $records = $this->dbBreakingNews($language, $request, $limit);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $cacheKey,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }
}
