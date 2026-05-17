<?php
namespace App\Services;

use App\Models\Category;
use App\Models\Location;
use App\Models\Tag;
use App\Models\Event;
use App\Services\Cache\CategoryCacheService;
use App\Services\Cache\ContributorCacheService;
use App\Services\Cache\EventCacheService;
use App\Services\Cache\LocationCacheService;
use App\Services\Cache\NewsCacheService;
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

    public function __construct(
        CategoryCacheService $categoryCacheService,
        TagCacheService $tagCacheService,
        LocationCacheService $locationCacheService,
        EventCacheService $eventCacheService,
        ContributorCacheService $contributorCacheService,
        NewsCacheService $newsCacheService
    ) {
        $this->categoryCacheService    = $categoryCacheService;
        $this->tagCacheService         = $tagCacheService;
        $this->locationCacheService    = $locationCacheService;
        $this->eventCacheService       = $eventCacheService;
        $this->contributorCacheService = $contributorCacheService;
        $this->newsCacheService        = $newsCacheService;
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
        return $this->eventCacheService->lastPageNo('sitemap',[]);
    }

    public function getContributors(Request $request)
    {
        $page = $request->query('page', 1);
        return $this->contributorCacheService->records('sitemap', null, $page);
    }

    public function getContributorsLastPageNo()
    {
        return $this->contributorCacheService->lastPageNo('sitemap');
    }

    public function latestNewsesGetNewses()
    {
        return $this->newsCacheService->getLatest("sitemap");
    }

    public function getNewses(Request $request)
    {
        return $this->newsCacheService->newses("sitemap", $request->input());
    }

    public function getNewsesLastPageNo(Request $request)
    {
        return $this->newsCacheService->lastPageNo("sitemap", $request->input());
    }

    public function getCategoryNewses(Request $request, Category $category)
    {
        $request->merge([
            'category_id' => $category->id,
        ]);
        return $this->newsCacheService->newses("sitemap", $request->input());
    }

    public function getCategoryNewsesLastPageNo(Request $request, Category $category)
    {
        $request->merge([
            'category_id' => $category->id,
        ]);
        return $this->newsCacheService->lastPageNo("sitemap", $request->input());
    }

    public function getLocationNewses(Request $request, Location $location)
    {
        $request->merge([
            'location_id' => $location->id,
        ]);
        return $this->newsCacheService->newses("sitemap", $request->input());
    }

    public function getLocationNewsesLastPageNo(Request $request, Location $location)
    {
        $request->merge([
            'location_id' => $location->id,
        ]);
        return $this->newsCacheService->lastPageNo("sitemap", $request->input());
    }

    public function getEventNewses(Request $request, Event $event)
    {
        $request->merge([
            'event_id' => $event->id,
        ]);
        return $this->newsCacheService->newses("sitemap", $request->input());
    }

    public function getEventNewsesLastPageNo(Request $request, Event $event)
    {
        $request->merge([
            'event_id' => $event->id,
        ]);
        return $this->newsCacheService->lastPageNo("sitemap", $request->input());
    }

    public function getTagNewses(Request $request, Tag $tag)
    {
        $request->merge([
            'tag_id' => $tag->id,
        ]);
        return $this->newsCacheService->newses("sitemap", $request->input());
    }

    public function getTagNewsesLastPageNo(Request $request, Tag $tag)
    {
        $request->merge([
            'tag_id' => $tag->id,
        ]);
        return $this->newsCacheService->lastPageNo("sitemap", $request->input());
    }
}
