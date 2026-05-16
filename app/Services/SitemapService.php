<?php
namespace App\Services;

use App\Models\Category;
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
        return Category::where("slug_tree", $slugTree)->firstOrFail();
    }

    public function getCategories(Request $request)
    {
        $page = $request->query('page', 1);
        return $this->categoryCacheService->categories('sitemap', null, $page);
    }

    public function getCategoriesLastPageNo()
    {
        return $this->categoryCacheService->lastPageNo('sitemap');
    }

    public function getTags(Request $request)
    {
        $page = $request->query('page', 1);
        return $this->tagCacheService->records('sitemap', null, $page);
    }

    public function getTagsLastPageNo()
    {
        return $this->tagCacheService->lastPageNo('sitemap');
    }

    public function getLocations(Request $request)
    {
        $page = $request->query('page', 1);
        return $this->locationCacheService->records('sitemap', null, $page);
    }

    public function getLocationsLastPageNo()
    {
        return $this->locationCacheService->lastPageNo('sitemap');
    }

    public function getEvents(Request $request)
    {
        $page = $request->query('page', 1);
        return $this->eventCacheService->records('sitemap', null, $page);
    }

    public function getEventsLastPageNo()
    {
        return $this->eventCacheService->lastPageNo('sitemap');
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

    public function latestNewsesGetNewses(Request $request)
    {
        return $this->newsCacheService->getLatest($request, "sitemap");
    }

    public function getNewses(Request $request)
    {
        $page = $request->query('page', 1);
        return $this->newsCacheService->newses($request, "sitemap");
    }

    public function getNewsesLastPageNo(Request $request)
    {
        return $this->newsCacheService->lastPageNo($request, "sitemap");
    }

    public function getCategoryNewses(Request $request, Category $category)
    {
        $request->merge([
            'category_id' => $category->id,
        ]);
        return $this->newsCacheService->newses($request, "sitemap");
    }

    public function getCategoryNewsesLastPageNo(Request $request, Category $category)
    {
        $request->merge([
            'category_id' => $category->id,
        ]);
        return $this->newsCacheService->lastPageNo($request, "sitemap");
    }
}
