<?php

namespace App\Http\Controllers;

use App\Services\FeedService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeedController extends Controller
{
    protected FeedService $feedService;

    public function __construct(FeedService $feedService)
    {
        $this->feedService = $feedService;
    }

    public function latestNewses(Request $request): View
    {
        $feedLink = $request->fullUrl();
        $selfUrl = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $newses = $this->feedService->latestNewses();

        return view('feeds.latest-newses', compact("newses","feedLink",'selfUrl','viewsType'));
    }

    public function newses(Request $request): View
    {
        $feedLink = $request->fullUrl();
        $selfUrl = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $newses = $this->feedService->getNewses($request);

        return view('feeds.newses', compact("newses","feedLink",'selfUrl','viewsType'));
    }

    private function viewsType(Request $request): string
    {
        $viewsType = strtoupper((string) $request->attributes->get('viewsType', 'RSS'));

        return in_array($viewsType, ['RSS', 'ATOM'], true)
            ? $viewsType
            : 'RSS';
    }
}
