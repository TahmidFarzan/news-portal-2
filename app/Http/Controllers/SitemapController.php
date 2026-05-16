<?php
namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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

    public function latestNewses(Request $request)
    {
        $records = $this->sitemapService->latestNewsesGetNewses($request);

        return response()->view('sitemaps.newses', compact('records'));
    }

    public function newses(Request $request)
    {
        if ($request->filled("page")) {
            $records = $this->sitemapService->getNewses($request);
            return response()->view('sitemaps.newses', compact('records'));
        }

        $routeUrl = route('sitemaps.newses');
        $lastPage = $this->sitemapService->getNewsesLastPageNo($request);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function categoryNewses(Request $request, $slugTree)
    {
        $category = $this->sitemapService->categoryBySlugTree($slugTree);

        if ($request->filled("page")) {
            $records = $this->sitemapService->getCategoryNewses($request, $category);
            return response()->view('sitemaps.newses', compact('records'));
        }

        $routeUrl = $category->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getCategoryNewsesLastPageNo($request, $category);

        return response()->view('sitemaps.paginable-index', compact('lastPage', "routeUrl"));

    }


}
