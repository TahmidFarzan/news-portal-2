<?php
namespace App\Services;

use App\Services\Cache\CategoryCacheService;
use App\Services\Cache\EventCacheService;
use App\Services\Cache\TagCacheService;
use Illuminate\Http\Request;

class SitemapService
{
    protected CategoryCacheService $categoryCacheService;
    protected EventCacheService $eventCacheService;
    protected TagCacheService $tagCacheService;

    public function __construct(
        CategoryCacheService $categoryCacheService,
        EventCacheService $eventCacheService,
        TagCacheService $tagCacheService,
    ) {
        $this->categoryCacheService = $categoryCacheService;
        $this->eventCacheService = $eventCacheService;
        $this->tagCacheService      = $tagCacheService;
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
