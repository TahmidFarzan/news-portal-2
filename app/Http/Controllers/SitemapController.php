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
        return response()->view('sitemaps.index')
            ->header('Content-Type', 'application/xml');
    }

    public function categories(Request $request)
    {
        if ($request->filled('page')) {
            $records = $this->sitemapService->getCategories($request);
            return response()->view('sitemaps.attributes', compact('records'))
                ->header('Content-Type', 'application/xml');
        }

        $routeFor = 'Category';
        $lastPage = $this->sitemapService->getCategoriesLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeFor'))
            ->header('Content-Type', 'application/xml');
    }

    public function tags(Request $request)
    {
        if ($request->filled('page')) {
            $records = $this->sitemapService->getTags($request);
            return response()->view('sitemaps.attributes', compact('records'))
                ->header('Content-Type', 'application/xml');
        }

        $routeFor = 'Tag';
        $lastPage = $this->sitemapService->getTagsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeFor'))
            ->header('Content-Type', 'application/xml');
    }

    public function locations(Request $request)
    {
        if ($request->filled('page')) {
            $records = $this->sitemapService->getLocations($request);
            return response()->view('sitemaps.attributes', compact('records'))
                ->header('Content-Type', 'application/xml');
        }

        $routeFor = 'Location';
        $lastPage = $this->sitemapService->getLocationsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeFor'))
            ->header('Content-Type', 'application/xml');
    }

    public function events(Request $request)
    {
        if ($request->filled('page')) {
            $records = $this->sitemapService->getEvents($request);
            return response()->view('sitemaps.attributes', compact('records'))
                ->header('Content-Type', 'application/xml');
        }

        $routeFor = 'Event';
        $lastPage = $this->sitemapService->getEventsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeFor'))
            ->header('Content-Type', 'application/xml');
    }

    public function contributors(Request $request)
    {
        if ($request->filled('page')) {
            $records = $this->sitemapService->getContributors($request);
            return response()->view('sitemaps.attributes', compact('records'))
                ->header('Content-Type', 'application/xml');
        }

        $routeFor = 'Contributor';
        $lastPage = $this->sitemapService->getContributorsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeFor'))
            ->header('Content-Type', 'application/xml');
    }

}
