<?php

namespace App\Services;

use App\Helpers\CacheHelper;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Location;
use App\Models\Tag;
use App\Services\Cache\CategoryCacheService;
use App\Services\Cache\ContributorCacheService;
use App\Services\Cache\EventCacheService;
use App\Services\Cache\LocationCacheService;
use App\Services\Cache\NewsCacheService;
use App\Services\Cache\PageCacheService;
use App\Services\Cache\TagCacheService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class SitemapService
{
    protected int $cachedTTL = 300;

    protected CategoryCacheService $categoryCacheService;

    protected LocationCacheService $locationCacheService;

    protected EventCacheService $eventCacheService;

    protected ContributorCacheService $contributorCacheService;

    protected TagCacheService $tagCacheService;

    protected NewsCacheService $newsCacheService;

    protected PageCacheService $pageCacheService;

    public function __construct(
        CategoryCacheService $categoryCacheService,
        TagCacheService $tagCacheService,
        LocationCacheService $locationCacheService,
        EventCacheService $eventCacheService,
        ContributorCacheService $contributorCacheService,
        NewsCacheService $newsCacheService,
        PageCacheService $pageCacheService
    ) {
        $this->categoryCacheService = $categoryCacheService;
        $this->tagCacheService = $tagCacheService;
        $this->locationCacheService = $locationCacheService;
        $this->eventCacheService = $eventCacheService;
        $this->contributorCacheService = $contributorCacheService;
        $this->newsCacheService = $newsCacheService;
        $this->pageCacheService = $pageCacheService;
    }

    public function categoryBySlugTree(string $slugTree): Category
    {
        return $this->categoryCacheService->getRecordBySlugTree(CacheHelper::KEY_SITEMAP, $slugTree, null, null);
    }

    public function tag(string $slug): Tag
    {
        return $this->tagCacheService->getRecordBySlug(CacheHelper::KEY_SITEMAP, $slug, null, null);
    }

    public function locationBySlugTree(string $slugTree): Location
    {
        return $this->locationCacheService->getRecordBySlugTree(CacheHelper::KEY_SITEMAP, $slugTree, null, null);
    }

    public function event(string $slug): Event
    {
        return $this->eventCacheService->getRecordBySlug(CacheHelper::KEY_SITEMAP, $slug, null, null);
    }

    public function contributor(string $slug): Contributor
    {
        return $this->contributorCacheService->getRecordBySlug(CacheHelper::KEY_SITEMAP, $slug, null, null);
    }

    public function getCategories(Request $request): LengthAwarePaginator
    {

        return $this->categoryCacheService->getRecords(CacheHelper::KEY_SITEMAP, $request);
    }

    public function getCategoriesLastPageNo(): int
    {
        return $this->categoryCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP);
    }

    public function getTags(Request $request): LengthAwarePaginator
    {
        return $this->tagCacheService->getRecords(CacheHelper::KEY_SITEMAP, $request);
    }

    public function getTagsLastPageNo(): int
    {
        return $this->tagCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP);
    }

    public function getLocations(Request $request): LengthAwarePaginator
    {
        return $this->locationCacheService->getRecords(CacheHelper::KEY_SITEMAP, $request);
    }

    public function getLocationsLastPageNo(): int
    {
        return $this->locationCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP);
    }

    public function getEvents(Request $request): LengthAwarePaginator
    {
        return $this->eventCacheService->getRecords(CacheHelper::KEY_SITEMAP, $request);
    }

    public function getEventsLastPageNo(): int
    {
        return $this->eventCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP);
    }

    public function getContributors(Request $request): LengthAwarePaginator
    {
        return $this->contributorCacheService->getRecords(CacheHelper::KEY_SITEMAP, $request);
    }

    public function getContributorsLastPageNo(): int
    {
        return $this->contributorCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP);
    }

    public function latestNews(): Collection
    {
        return $this->newsCacheService->getLatestRecord(CacheHelper::KEY_SITEMAP, null, null, false, $this->cachedTTL);
    }

    public function getNews(Request $request): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $request);
    }

    public function getNewsLastPageNo(): int
    {
        return $this->newsCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP);
    }

    public function getCategoryNews(Request $request, Category $category): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $request, $category);
    }

    public function getCategoryNewsLastPageNo(Request $request, Category $category): int
    {
        return $this->newsCacheService->getLastPageNoByFilter(CacheHelper::KEY_SITEMAP, $request, $category);
    }

    public function getLocationNews(Request $request, Location $location): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $request, $location);
    }

    public function getLocationNewsLastPageNo(Request $request, Location $location): int
    {
        return $this->newsCacheService->getLastPageNoByFilter(CacheHelper::KEY_SITEMAP, $request, $location);
    }

    public function getEventNews(Request $request, Event $event): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $request, $event);
    }

    public function getEventNewsLastPageNo(Request $request, Event $event): int
    {
        return $this->newsCacheService->getLastPageNoByFilter(CacheHelper::KEY_SITEMAP, $request, $event);
    }

    public function getContributorNews(Request $request, Contributor $contributor): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $request, $contributor);
    }

    public function getContributorNewsLastPageNo(Request $request, Contributor $contributor): int
    {
        return $this->newsCacheService->getLastPageNoByFilter(CacheHelper::KEY_SITEMAP, $request, $contributor);
    }

    public function getTagNews(Request $request, Tag $tag): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_SITEMAP, $request, $tag);
    }

    public function getTagNewsLastPageNo(Request $request, Tag $tag): int
    {
        return $this->newsCacheService->getLastPageNoByFilter(CacheHelper::KEY_SITEMAP, $request, $tag);
    }

    public function getPages(Request $request): LengthAwarePaginator
    {
        return $this->pageCacheService->getRecords(CacheHelper::KEY_SITEMAP, $request);
    }

    public function getPagesLastPageNo(): int
    {
        return $this->pageCacheService->getLastPageNo(CacheHelper::KEY_SITEMAP);
    }
}
