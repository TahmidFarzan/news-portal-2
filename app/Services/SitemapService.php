<?php
namespace App\Services;

use App\Services\Cache\CategoryCacheService;
use Illuminate\Http\Request;

class SitemapService
{
    protected CategoryCacheService $categoryCacheService;

    public function __construct(
        CategoryCacheService $categoryCacheService,
    ) {
        $this->categoryCacheService = $categoryCacheService;
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
}
