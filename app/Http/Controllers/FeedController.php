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

    public function latestNews(Request $request): View
    {
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $newsItems    = $this->feedService->latestNews();

        return view('feeds.latest-news', compact("newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function news(Request $request): View
    {
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $newsItems    = $this->feedService->getNews($request);

        return view('feeds.news', compact("newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function categoryNews(Request $request, string $slugTree): View
    {
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->categoryBySlugTree($slugTree);
        $newsItems    = $this->feedService->getCategoryNews($request, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function locationNews(Request $request, string $slugTree): View
    {
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->locationBySlugTree($slugTree);
        $newsItems    = $this->feedService->getLocationNews($request, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function eventNews(Request $request, string $slug): View
    {
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->event($slug);
        $newsItems    = $this->feedService->getEventNews($request, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }


    public function tagNews(Request $request, string $slug): View
    {
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->tag($slug);
        $newsItems    = $this->feedService->getTagNews($request, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function contributorNews(Request $request, string $slug): View
    {
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->contributor($slug);
        $newsItems    = $this->feedService->getContributorNews($request, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    private function viewsType(Request $request): string
    {
        $viewsType = strtoupper((string) $request->attributes->get('viewsType', 'RSS'));

        return in_array($viewsType, ['RSS', 'ATOM'], true)
            ? $viewsType
            : 'RSS';
    }
}
