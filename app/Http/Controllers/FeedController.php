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
        $language = $this->feedService->defaultLanguage();

        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $newsItems = $this->feedService->latestNews($language);

        return view('feeds.latest-news', compact("newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function news(Request $request): View
    {
        $language  = $this->feedService->defaultLanguage();
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $newsItems = $this->feedService->getNews($request, $language);

        return view('feeds.news', compact("newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function categoryNews(Request $request, string $slugTree): View
    {
        $language  = $this->feedService->defaultLanguage();
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->categoryBySlugTree($language, $slugTree);
        $newsItems = $this->feedService->getCategoryNews($request, $language, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function locationNews(Request $request, string $slugTree): View
    {
        $language  = $this->feedService->defaultLanguage();
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->locationBySlugTree($language, $slugTree);
        $newsItems = $this->feedService->getLocationNews($request, $language, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function eventNews(Request $request, string $slug): View
    {
        $language  = $this->feedService->defaultLanguage();
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->event($language, $slug);
        $newsItems = $this->feedService->getEventNews($request, $language, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function tagNews(Request $request, string $slug): View
    {
        $language  = $this->feedService->defaultLanguage();
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->tag($language, $slug);
        $newsItems = $this->feedService->getTagNews($request, $language, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function contributorNews(Request $request, string $slug): View
    {
        $language  = $this->feedService->defaultLanguage();
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->contributor($language, $slug);
        $newsItems = $this->feedService->getContributorNews($request, $language, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function localizedLatestNews(Request $request, string $languageCode): View
    {
        $language = $this->feedService->language($languageCode);

        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $newsItems = $this->feedService->latestNews($language, );

        return view('feeds.latest-news', compact("newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function localizedNews(Request $request, string $languageCode): View
    {
        $language  = $this->feedService->language($languageCode);
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $newsItems = $this->feedService->getNews($request, $language, );

        return view('feeds.news', compact("newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function localizedCategoryNews(Request $request, string $languageCode, string $slugTree): View
    {
        $language  = $this->feedService->language($languageCode);
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->categoryBySlugTree($language, $slugTree);
        $newsItems = $this->feedService->getCategoryNews($request, $language, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function localizedLocationNews(Request $request, string $languageCode, string $slugTree): View
    {
        $language  = $this->feedService->language($languageCode);
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->locationBySlugTree($language, $slugTree);
        $newsItems = $this->feedService->getLocationNews($request, $language, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function localizedEventNews(Request $request, string $languageCode, string $slug): View
    {
        $language  = $this->feedService->language($languageCode);
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->event($language, $slug);
        $newsItems = $this->feedService->getEventNews($request, $language, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function localizedTagNews(Request $request, string $languageCode, string $slug): View
    {
        $language  = $this->feedService->language($languageCode);
        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->tag($language, $slug);
        $newsItems = $this->feedService->getTagNews($request, $language, $attribute);

        return view('feeds.attribute-news', compact("attribute", "newsItems", "feedLink", 'selfUrl', 'viewsType'));
    }

    public function localizedContributorNews(Request $request, string $languageCode, string $slug): View
    {
        $language = $this->feedService->language($languageCode);

        $feedLink  = $request->fullUrl();
        $selfUrl   = $request->fullUrl();
        $viewsType = $this->viewsType($request);
        $attribute = $this->feedService->contributor($language, $slug);
        $newsItems = $this->feedService->getContributorNews($request, $language, $attribute);

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
