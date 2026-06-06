<?php
namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    protected SitemapService $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    public function index()
    {
        return response()->view('sitemaps.index');
    }

    public function categories(Request $request)
    {
        if ($request->filled('page')) {
            $records = $this->sitemapService->getCategories($request);
            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.categories');
        $lastPage = $this->sitemapService->getCategoriesLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function tags(Request $request)
    {
        if ($request->filled('page')) {
            $records = $this->sitemapService->getTags($request);
            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.tags');
        $lastPage = $this->sitemapService->getTagsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function locations(Request $request)
    {
        if ($request->filled('page')) {
            $records = $this->sitemapService->getLocations($request);
            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.locations');
        $lastPage = $this->sitemapService->getLocationsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function events(Request $request)
    {
        if ($request->filled('page')) {
            $records = $this->sitemapService->getEvents($request);
            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.events');
        $lastPage = $this->sitemapService->getEventsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function contributors(Request $request)
    {
        if ($request->filled('page')) {
            $records = $this->sitemapService->getContributors($request);
            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.contributors');
        $lastPage = $this->sitemapService->getContributorsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function latestNews(Request $request)
    {
        $records = $this->sitemapService->latestNews($request);

        return response()->view('sitemaps.news', compact('records'));
    }

    public function news(Request $request)
    {
        if ($request->filled("page")) {
            $records = $this->sitemapService->getNews($request);
            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = route('sitemaps.news');
        $lastPage = $this->sitemapService->getNewsLastPageNo($request);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function categoryNews(Request $request, $slugTree)
    {
        $category = $this->sitemapService->categoryBySlugTree($slugTree);

        if ($request->filled("page")) {
            $records = $this->sitemapService->getCategoryNews($request, $category);
            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $category->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getCategoryNewsLastPageNo($request, $category);

        return response()->view('sitemaps.paginable-index', compact('lastPage', "routeUrl"));

    }

    public function tagNews(Request $request, $slug)
    {
        $tag = $this->sitemapService->tag($slug);

        if ($request->filled("page")) {
            $records = $this->sitemapService->getTagNews($request, $tag);
            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $tag->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getTagNewsLastPageNo($request, $tag);

        return response()->view('sitemaps.paginable-index', compact('lastPage', "routeUrl"));

    }

    public function locationNews(Request $request, $slugTree)
    {
        $location = $this->sitemapService->locationBySlugTree($slugTree);

        if ($request->filled("page")) {
            $records = $this->sitemapService->getLocationNews($request, $location);
            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $location->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getLocationNewsLastPageNo($request, $location);

        return response()->view('sitemaps.paginable-index', compact('lastPage', "routeUrl"));

    }

    public function eventNews(Request $request, $slug)
    {
        $event = $this->sitemapService->event($slug);

        if ($request->filled("page")) {
            $records = $this->sitemapService->getEventNews($request, $event);
            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $event->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getEventNewsLastPageNo($request, $event);

        return response()->view('sitemaps.paginable-index', compact('lastPage', "routeUrl"));
    }

    public function contributorNews(Request $request, $slug)
    {
        $contributor = $this->sitemapService->contributor($slug);

        if ($request->filled("page")) {
            $records = $this->sitemapService->getContributorNews($request, $contributor);
            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $contributor->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getContributorNewsLastPageNo($request, $contributor);

        return response()->view('sitemaps.paginable-index', compact('lastPage', "routeUrl"));

    }
}
