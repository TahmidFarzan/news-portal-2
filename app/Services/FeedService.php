<?php
namespace App\Services;

use App\Models\Category;
use App\Models\Location;
use App\Models\Event;
use App\Models\Tag;
use App\Models\Contributor;
use App\Services\Cache\CategoryCacheService;
use App\Services\Cache\LocationCacheService;
use App\Services\Cache\EventCacheService;
use App\Services\Cache\TagCacheService;
use App\Services\Cache\ContributorCacheService;
use App\Services\Cache\NewsCacheService;
use Illuminate\Http\Request;

class FeedService
{
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
    )
    {
        $this->newsCacheService     = $newsCacheService;
        $this->categoryCacheService = $categoryCacheService;
        $this->locationCacheService = $locationCacheService;
        $this->eventCacheService = $eventCacheService;
        $this->tagCacheService = $tagCacheService;
        $this->contributorCacheService = $contributorCacheService;
    }

    public function categoryBySlugTree(string $slugTree): Category
    {
        return $this->categoryCacheService->categoryBySlugTree($slugTree);
    }

    public function locationBySlugTree(string $slugTree): Location
    {
        return $this->locationCacheService->locationBySlugTree($slugTree);
    }

    public function event(string $slug): Event
    {
        return $this->eventCacheService->event($slug);
    }

    public function tag(string $slug): Tag
    {
        return $this->tagCacheService->tag($slug);
    }

    public function contributor(string $slug): Contributor
    {
        return $this->contributorCacheService->contributor($slug);
    }

    public function latestNewses()
    {
        return $this->newsCacheService->getLatest("feed");
    }

    public function getNewses(Request $request)
    {
        return $this->newsCacheService->newses("feed", $request->input());
    }

    public function getNewsesLastPageNo(Request $request)
    {
        return $this->newsCacheService->lastPageNo("feed", $request->input());
    }

    public function getCategoryNewses(Request $request, Category $category)
    {
        $request->merge([
            'category_id' => $category->id,
        ]);
        return $this->newsCacheService->newses("feed", $request->input());
    }

    public function getLocationNewses(Request $request, Location $location)
    {
        $request->merge([
            'location_id' => $location->id,
        ]);
        return $this->newsCacheService->newses("feed", $request->input());
    }

    public function getEventNewses(Request $request, Event $event)
    {
        $request->merge([
            'event_id' => $event->id,
        ]);
        return $this->newsCacheService->newses("feed", $request->input());
    }

    public function getTagNewses(Request $request, Tag $tag)
    {
        $request->merge([
            'tag_id' => $tag->id,
        ]);
        return $this->newsCacheService->newses("feed", $request->input());
    }

    public function getContributorNewses(Request $request, Contributor $contributor)
    {
        $request->merge([
            'contributor_id' => $contributor->id,
        ]);
        return $this->newsCacheService->newses("feed", $request->input());
    }
}
