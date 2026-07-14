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
use App\Services\Cache\TagCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FeedService
{
    private int $cachedTTL = 300;

    protected NewsCacheService $newsCacheService;

    protected CategoryCacheService $categoryCacheService;

    protected LocationCacheService $locationCacheService;

    protected EventCacheService $eventCacheService;

    protected TagCacheService $tagCacheService;

    protected ContributorCacheService $contributorCacheService;

    public function __construct(
        NewsCacheService $newsCacheService,
        CategoryCacheService $categoryCacheService,
        LocationCacheService $locationCacheService,
        EventCacheService $eventCacheService,
        TagCacheService $tagCacheService,
        ContributorCacheService $contributorCacheService
    ) {
        $this->newsCacheService = $newsCacheService;
        $this->categoryCacheService = $categoryCacheService;
        $this->locationCacheService = $locationCacheService;
        $this->eventCacheService = $eventCacheService;
        $this->tagCacheService = $tagCacheService;
        $this->contributorCacheService = $contributorCacheService;
    }

    public function categoryBySlugTree(string $slugTree): Category
    {
        return $this->categoryCacheService->getRecordBySlugTree(CacheHelper::KEY_FEED, $slugTree);
    }

    public function locationBySlugTree(string $slugTree): Location
    {
        return $this->locationCacheService->getRecordBySlugTree(CacheHelper::KEY_FEED, $slugTree);
    }

    public function event(string $slug): Event
    {
        return $this->eventCacheService->getRecordBySlug(CacheHelper::KEY_FEED, $slug);
    }

    public function tag(string $slug): Tag
    {
        return $this->tagCacheService->getRecordBySlug(CacheHelper::KEY_SITEMAP, $slug);
    }

    public function contributor(string $slug): Contributor
    {
        return $this->contributorCacheService->getRecordBySlug(CacheHelper::KEY_FEED, $slug);
    }

    public function latestNews(): Collection
    {
        return $this->newsCacheService->getLatestRecord(CacheHelper::KEY_FEED, null, null, false, $this->cachedTTL);
    }

    public function getNews(Request $request): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $request);
    }

    public function getNewsLastPageNo(): int
    {
        return $this->newsCacheService->getLastPageNo(CacheHelper::KEY_FEED);
    }

    public function getCategoryNews(Request $request, Category $category): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $request, $category);
    }

    public function getLocationNews(Request $request, Location $location): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $request, $location);
    }

    public function getEventNews(Request $request, Event $event): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $request, $event);
    }

    public function getTagNews(Request $request, Tag $tag): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $request, $tag);
    }

    public function getContributorNews(Request $request, Contributor $contributor): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $request, $contributor);
    }
}
