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

    public function index(Request $request): Response
    {
        $pages = $this->sitemapService->getPages($request);

        return response()->view('sitemaps.index', compact('pages'));
    }

    public function categories(Request $request): Response
    {
        if ($request->input()) {
            $records = $this->sitemapService->getCategories($request);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.categories');
        $lastPage = $this->sitemapService->getCategoriesLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function tags(Request $request): Response
    {
        if ($request->input()) {
            $records = $this->sitemapService->getTags($request);

            return response()->view('sitemaps.attributes', compact('records'));
        }
        $routeUrl = route('sitemaps.tags');
        $lastPage = $this->sitemapService->getTagsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function locations(Request $request): Response
    {
        if ($request->input()) {
            $records = $this->sitemapService->getLocations($request);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.locations');
        $lastPage = $this->sitemapService->getLocationsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function events(Request $request): Response
    {
        if ($request->input()) {
            $records = $this->sitemapService->getEvents($request);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.events');
        $lastPage = $this->sitemapService->getEventsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function contributors(Request $request): Response
    {
        if ($request->input()) {
            $records = $this->sitemapService->getContributors($request);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.contributors');
        $lastPage = $this->sitemapService->getContributorsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function latestNews(Request $request): Response
    {
        $records = $this->sitemapService->latestNews();

        return response()->view('sitemaps.news', compact('records'));
    }

    public function news(Request $request): Response
    {
        if ($request->input()) {
            $records = $this->sitemapService->getNews($request);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = route('sitemaps.news');
        $lastPage = $this->sitemapService->getNewsLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function categoryNews(Request $request, string $slugTree): Response
    {
        $category = $this->sitemapService->categoryBySlugTree($slugTree);

        if ($request->input()) {
            $records = $this->sitemapService->getCategoryNews($request, $category);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $category->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getCategoryNewsLastPageNo($request, $category);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }

    public function tagNews(Request $request, string $slug): Response
    {
        $tag = $this->sitemapService->tag($slug);

        if ($request->input()) {
            $records = $this->sitemapService->getTagNews($request, $tag);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $tag->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getTagNewsLastPageNo($request, $tag);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }

    public function locationNews(Request $request, string $slugTree): Response
    {
        $location = $this->sitemapService->locationBySlugTree($slugTree);

        if ($request->input()) {
            $records = $this->sitemapService->getLocationNews($request, $location);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $location->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getLocationNewsLastPageNo($request, $location);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }

    public function eventNews(Request $request, string $slug): Response
    {
        $event = $this->sitemapService->event($slug);

        if ($request->input()) {
            $records = $this->sitemapService->getEventNews($request, $event);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $event->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getEventNewsLastPageNo($request, $event);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function contributorNews(Request $request, string $slug): Response
    {
        $contributor = $this->sitemapService->contributor($slug);

        if ($request->input()) {
            $records = $this->sitemapService->getContributorNews($request, $contributor);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $contributor->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getContributorNewsLastPageNo($request, $contributor);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }
}
