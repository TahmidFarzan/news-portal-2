<?php
namespace App\Services;

use App\Services\Cache\CategoryCacheService;
use App\Services\Cache\EventCacheService;
use App\Services\Cache\LocationCacheService;
use App\Services\Cache\TagCacheService;
use Illuminate\Http\Request;

class SitemapService
{
    protected CategoryCacheService $categoryCacheService;
    protected LocationCacheService $locationCacheService;
    protected EventCacheService $eventCacheService;
    protected TagCacheService $tagCacheService;

    public function __construct(
        CategoryCacheService $categoryCacheService,
        TagCacheService $tagCacheService,
        LocationCacheService $locationCacheService,
        EventCacheService $eventCacheService
    ) {
        $this->categoryCacheService = $categoryCacheService;
        $this->tagCacheService      = $tagCacheService;
        $this->locationCacheService = $locationCacheService;
        $this->eventCacheService    = $eventCacheService;

    }

    public function getCategories(Request $request)
    {
        $page = $request->query('page', 1);
        return $this->categoryCacheService->records('sitemap', null, $page);
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
}
