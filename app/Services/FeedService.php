<?php
namespace App\Services;

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
use App\Services\Cache\TagCacheService;
use App\Services\SiteService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class FeedService
{
    private int $cachedTTL = 300;

    protected SiteService $siteService;
    protected NewsCacheService $newsCacheService;

    protected CategoryCacheService $categoryCacheService;

    protected LocationCacheService $locationCacheService;

    protected EventCacheService $eventCacheService;

    protected TagCacheService $tagCacheService;

    protected ContributorCacheService $contributorCacheService;

    public function __construct(
        SiteService $siteService,
        NewsCacheService $newsCacheService,
        CategoryCacheService $categoryCacheService,
        LocationCacheService $locationCacheService,
        EventCacheService $eventCacheService,
        TagCacheService $tagCacheService,
        ContributorCacheService $contributorCacheService
    ) {
        $this->siteService             = $siteService;
        $this->newsCacheService        = $newsCacheService;
        $this->categoryCacheService    = $categoryCacheService;
        $this->locationCacheService    = $locationCacheService;
        $this->eventCacheService       = $eventCacheService;
        $this->tagCacheService         = $tagCacheService;
        $this->contributorCacheService = $contributorCacheService;
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
        return $this->categoryCacheService->getRecordBySlugTree(CacheHelper::KEY_FEED, $language, $slugTree);
    }

    public function locationBySlugTree(Language $language, string $slugTree): Location
    {
        return $this->locationCacheService->getRecordBySlugTree(CacheHelper::KEY_FEED, $language, $slugTree);
    }

    public function event(Language $language, string $slug): Event
    {
        return $this->eventCacheService->getRecordBySlug(CacheHelper::KEY_FEED, $language, $slug);
    }

    public function tag(Language $language, string $slug): Tag
    {
        return $this->tagCacheService->getRecordBySlug(CacheHelper::KEY_SITEMAP, $language, $slug);
    }

    public function contributor(Language $language, string $slug): Contributor
    {
        return $this->contributorCacheService->getRecordBySlug(CacheHelper::KEY_FEED, $language, $slug);
    }

    public function latestNews(Language $language): Collection
    {
        return $this->newsCacheService->getLatestRecord(CacheHelper::KEY_FEED, $language, null, false, $this->cachedTTL);
    }

    public function getNews(Request $request, Language $language, ): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $language, $request);
    }

    public function getNewsLastPageNo(Language $language, ): int
    {
        return $this->newsCacheService->getLastPageNo(CacheHelper::KEY_FEED, $language, );
    }

    public function getCategoryNews(Request $request, Language $language, Category $category): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $language, $request, $category);
    }

    public function getLocationNews(Request $request, Language $language, Location $location): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $language, $request, $location);
    }

    public function getEventNews(Request $request, Language $language, Event $event): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $language, $request, $event);
    }

    public function getTagNews(Request $request, Language $language, Tag $tag): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $language, $request, $tag);
    }

    public function getContributorNews(Request $request, Language $language, Contributor $contributor): Collection
    {
        return $this->newsCacheService->getRecordsLimit(CacheHelper::KEY_FEED, $language, $request, $contributor);
    }
}
