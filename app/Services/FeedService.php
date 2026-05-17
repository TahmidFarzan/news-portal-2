<?php
namespace App\Services;

use App\Services\Cache\NewsCacheService;
use Illuminate\Http\Request;

class FeedService
{
    protected NewsCacheService $newsCacheService;

    public function __construct(NewsCacheService $newsCacheService)
    {
        $this->newsCacheService = $newsCacheService;
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
}
