<?php
namespace App\Services;

use App\Helpers\SystemHelper;
use App\Helpers\CacheHelper;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\Tag;
use App\Services\Cache\CategoryCacheService;
use App\Services\Cache\ContributorCacheService;
use App\Services\Cache\EventCacheService;
use App\Services\Cache\LocationCacheService;
use App\Services\Cache\NewsCacheService;
use App\Services\Cache\PageCacheService;
use App\Services\Cache\TagCacheService;
use App\Services\SiteService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SitemapService
{
    protected int $cachedTTL = 300;

    protected SiteService $siteService;
    protected CategoryCacheService $categoryCacheService;

    protected LocationCacheService $locationCacheService;

    protected EventCacheService $eventCacheService;

    protected ContributorCacheService $contributorCacheService;

    protected TagCacheService $tagCacheService;

    protected NewsCacheService $newsCacheService;

    protected PageCacheService $pageCacheService;

    public function __construct(
        SiteService $siteService,
        CategoryCacheService $categoryCacheService,
        TagCacheService $tagCacheService,
        LocationCacheService $locationCacheService,
        EventCacheService $eventCacheService,
        ContributorCacheService $contributorCacheService,
        NewsCacheService $newsCacheService,
        PageCacheService $pageCacheService
    ) {
        $this->siteService             = $siteService;
        $this->categoryCacheService    = $categoryCacheService;
        $this->tagCacheService         = $tagCacheService;
        $this->locationCacheService    = $locationCacheService;
        $this->eventCacheService       = $eventCacheService;
        $this->contributorCacheService = $contributorCacheService;
        $this->newsCacheService        = $newsCacheService;
        $this->pageCacheService        = $pageCacheService;
    }

    public function language(string $code): Language
    {
        return $this->siteService->language($code);
    }

    public function defaultLanguage(): Language
    {
        return $this->siteService->defaultLanguage();
    }

    public function categoryBySlugTree(Language $language, string $slugTree): Category
    {
        return $this->categoryCacheService->getRecordBySlugTree(CacheHelper::KEY_SITEMAP, $language, $slugTree, null, );
    }

    public function tag(Language $language, string $slug): Tag
    {
        return $this->tagCacheService->getRecordBySlug(CacheHelper::KEY_SITEMAP, $language, $slug, null);
    }

    public function locationBySlugTree(Language $language, string $slugTree): Location
    {
        return $this->locationCacheService->getRecordBySlugTree(CacheHelper::KEY_SITEMAP, $language, $slugTree, null);
    }

    public function event(Language $language, string $slug): Event
    {
        return $this->eventCacheService->getRecordBySlug(CacheHelper::KEY_SITEMAP, $language, $slug, null);
    }

    public function contributor(Language $language, string $slug): Contributor
    {
        return $this->contributorCacheService->getRecordBySlug(CacheHelper::KEY_SITEMAP, $language, $slug, null);
    }

    public function getCategories(Request $request, Language $language, ): LengthAwarePaginator
    {

        return $this->categoryCacheService->getRecords(CacheHelper::KEY_SITEMAP, $request, $language, );
    }

    public function getCategoriesLastPageNo(Language $language, ): int
    {
        return $this->categoryCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP, $language, );
    }

    public function getTags(Request $request, Language $language, ): LengthAwarePaginator
    {
        return $this->tagCacheService->getRecords(CacheHelper::KEY_SITEMAP, $language, $request, );
    }

    public function getTagsLastPageNo(Language $language, ): int
    {
        return $this->tagCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP, $language, );
    }

    public function getLocations(Request $request, Language $language, ): LengthAwarePaginator
    {
        return $this->locationCacheService->getRecords(CacheHelper::KEY_SITEMAP, $request, $language, );
    }

    public function getLocationsLastPageNo(Language $language, ): int
    {
        return $this->locationCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP, $language, );
    }

    public function getEvents(Request $request, Language $language, ): LengthAwarePaginator
    {
        return $this->eventCacheService->getRecords(CacheHelper::KEY_SITEMAP, $request, $language, );
    }

    public function getEventsLastPageNo(Language $language, ): int
    {
        return $this->eventCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP, $language, );
    }

    public function getContributors(Request $request, Language $language, ): LengthAwarePaginator
    {
        return $this->contributorCacheService->getRecords(CacheHelper::KEY_SITEMAP, $request, $language, );
    }

    public function getContributorsLastPageNo(Language $language, ): int
    {
        return $this->contributorCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP, $language, );
    }

    public function latestNews(Language $language, ): Collection
    {
        return $this->newsCacheService->getLatestRecord(CacheHelper::KEY_SITEMAP, $language, null, false, $this->cachedTTL);
    }

    public function getNews(Request $request, Language $language, ): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $language, $request);
    }

    public function getNewsLastPageNo(Language $language, ): int
    {
        return $this->newsCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP, $language, );
    }

    public function getCategoryNews(Request $request, Language $language, Category $category, ): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $language, $request, $category);
    }

    public function getCategoryNewsLastPageNo(Request $request, Language $language, Category $category): int
    {
        return $this->newsCacheService->getLastPageNoByFilter(CacheHelper::KEY_SITEMAP, $language, $request, $category);
    }

    public function getLocationNews(Request $request, Language $language, Location $location): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $language, $request, $location);
    }

    public function getLocationNewsLastPageNo(Request $request, Language $language, Location $location): int
    {
        return $this->newsCacheService->getLastPageNoByFilter(CacheHelper::KEY_SITEMAP, $language, $request, $location);
    }

    public function getEventNews(Request $request, Language $language, Event $event): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $language, $request, $event);
    }

    public function getEventNewsLastPageNo(Request $request, Language $language, Event $event): int
    {
        return $this->newsCacheService->getLastPageNoByFilter(CacheHelper::KEY_SITEMAP, $language, $request, $event);
    }

    public function getContributorNews(Request $request, Language $language, Contributor $contributor): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $language, $request, $contributor);
    }

    public function getContributorNewsLastPageNo(Request $request, Language $language, Contributor $contributor): int
    {
        return $this->newsCacheService->getLastPageNoByFilter(CacheHelper::KEY_SITEMAP, $language, $request, $contributor);
    }

    public function getTagNews(Request $request, Language $language, Tag $tag): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $language, $request, $tag);
    }

    public function getTagNewsLastPageNo(Request $request, Language $language, Tag $tag): int
    {
        return $this->newsCacheService->getLastPageNoByFilter(CacheHelper::KEY_SITEMAP, $language, $request, $tag);
    }

    public function getPages(Request $request, Language $language, ): LengthAwarePaginator
    {
        return $this->pageCacheService->getRecords(CacheHelper::KEY_SITEMAP, $request, $language);
    }

    public function getPagesLastPageNo(Language $language, ): int
    {
        return $this->pageCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP, $language);
    }

    public function cachedLatestNews(){
        return $this->newsCacheService->cachedLatestRecord(CacheHelper::KEY_SITEMAP, null, false, $this->cachedTTL);
    }
}
