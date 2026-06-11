<?php
namespace App\Services;

use App\Models\Category;
use App\Models\Event;
use App\Models\Location;
use App\Models\Tag;
use App\Models\Contributor;
use App\Services\Cache\CategoryCacheService;
use App\Services\Cache\ContributorCacheService;
use App\Services\Cache\EventCacheService;
use App\Services\Cache\LocationCacheService;
use App\Services\Cache\NewsCacheService;
use App\Services\Cache\PageCacheService;
use App\Services\Cache\TagCacheService;
use Illuminate\Http\Request;

class SitemapService
{
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
        $this->categoryCacheService    = $categoryCacheService;
        $this->tagCacheService         = $tagCacheService;
        $this->locationCacheService    = $locationCacheService;
        $this->eventCacheService       = $eventCacheService;
        $this->contributorCacheService = $contributorCacheService;
        $this->newsCacheService        = $newsCacheService;
        $this->pageCacheService        = $pageCacheService;
    }

    public function categoryBySlugTree(string $slugTree): Category
    {
        return $this->categoryCacheService->categoryBySlugTree($slugTree);
    }

    public function tag(string $slug): Tag
    {
        return $this->tagCacheService->tag($slug);
    }

    public function locationBySlugTree(string $slugTree): Location
    {
        return $this->locationCacheService->locationBySlugTree($slugTree);
    }

    public function event(string $slug): Event
    {
        return $this->eventCacheService->event($slug);
    }

    public function contributor(string $slug): Contributor
    {
        return $this->contributorCacheService->contributor($slug);
    }

    public function getCategories(Request $request)
    {
        return $this->categoryCacheService->categories('sitemap', $request->input());
    }

    public function getCategoriesLastPageNo()
    {
        return $this->categoryCacheService->lastPageNo('sitemap');
    }

    public function getTags(Request $request)
    {
        return $this->tagCacheService->tags('sitemap', $request->input());
    }

    public function getTagsLastPageNo()
    {
        return $this->tagCacheService->lastPageNo('sitemap', []);
    }

    public function getLocations(Request $request)
    {
        return $this->locationCacheService->locations('sitemap', $request->input());
    }

    public function getLocationsLastPageNo()
    {
        return $this->locationCacheService->lastPageNo('sitemap', []);
    }

    public function getEvents(Request $request)
    {
        return $this->eventCacheService->events('sitemap', $request->input());
    }

    public function getEventsLastPageNo()
    {
        return $this->eventCacheService->lastPageNo('sitemap', []);
    }

    public function getContributors(Request $request)
    {
        return $this->contributorCacheService->contributors('sitemap', $request->input());
    }

    public function getContributorsLastPageNo()
    {
        return $this->contributorCacheService->lastPageNo('sitemap',[]);
    }

    public function latestNews()
    {
        return $this->newsCacheService->getLatest("sitemap");
    }

    public function getNews(Request $request)
    {
        return $this->newsCacheService->news("sitemap", $request->input());
    }

    public function getNewsLastPageNo(Request $request)
    {
        return $this->newsCacheService->lastPageNo("sitemap", $request->input());
    }

    public function getCategoryNews(Request $request, Category $category)
    {
        $request->merge([
            'category_id' => $category->id,
        ]);
        return $this->newsCacheService->news("sitemap", $request->input());
    }

    public function getCategoryNewsLastPageNo(Request $request, Category $category)
    {
        $request->merge([
            'category_id' => $category->id,
        ]);
        return $this->newsCacheService->lastPageNo("sitemap", $request->input());
    }

    public function getLocationNews(Request $request, Location $location)
    {
        $request->merge([
            'location_id' => $location->id,
        ]);
        return $this->newsCacheService->news("sitemap", $request->input());
    }

    public function getLocationNewsLastPageNo(Request $request, Location $location)
    {
        $request->merge([
            'location_id' => $location->id,
        ]);
        return $this->newsCacheService->lastPageNo("sitemap", $request->input());
    }

    public function getEventNews(Request $request, Event $event)
    {
        $request->merge([
            'event_id' => $event->id,
        ]);
        return $this->newsCacheService->news("sitemap", $request->input());
    }

    public function getEventNewsLastPageNo(Request $request, Event $event)
    {
        $request->merge([
            'event_id' => $event->id,
        ]);
        return $this->newsCacheService->lastPageNo("sitemap", $request->input());
    }

    public function getContributorNews(Request $request, Contributor $contributor)
    {
        $request->merge([
            'contributor_id' => $contributor->id,
        ]);
        return $this->newsCacheService->news("sitemap", $request->input());
    }

    public function getContributorNewsLastPageNo(Request $request, Contributor $contributor)
    {
        $request->merge([
            'contributor_id' => $contributor->id,
        ]);
        return $this->newsCacheService->lastPageNo("sitemap", $request->input());
    }

    public function getTagNews(Request $request, Tag $tag)
    {
        $request->merge([
            'tag_id' => $tag->id,
        ]);
        return $this->newsCacheService->news("sitemap", $request->input());
    }

    public function getTagNewsLastPageNo(Request $request, Tag $tag)
    {
        $request->merge([
            'tag_id' => $tag->id,
        ]);
        return $this->newsCacheService->lastPageNo("sitemap", $request->input());
    }

    public function getPages(Request $request)
    {
        return $this->pageCacheService->pages("sitemap", $request->input());
    }

    public function getPagesLastPageNo(Request $request)
    {
        return $this->pageCacheService->lastPageNo("sitemap", $request->input());
    }
}
