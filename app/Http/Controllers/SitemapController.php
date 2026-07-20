<?php
namespace App\Http\Controllers;

use App\Helpers\SystemHelper;
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
        $language = $this->sitemapService->defaultLanguage();
        $pages    = $this->sitemapService->getPages($request, $language);

        return response()->view('sitemaps.index', compact('pages'));
    }

    public function categories(Request $request): Response
    {
        $language = $this->sitemapService->defaultLanguage();
        if ($request->input()) {
            $records = $this->sitemapService->getCategories($request, $language);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.categories');
        $lastPage = $this->sitemapService->getCategoriesLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function tags(Request $request): Response
    {
        $language = $this->sitemapService->defaultLanguage();
        if ($request->input()) {
            $records = $this->sitemapService->getTags($request, $language);

            return response()->view('sitemaps.attributes', compact('records'));
        }
        $routeUrl = route('sitemaps.tags');
        $lastPage = $this->sitemapService->getTagsLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function locations(Request $request): Response
    {
        $language = $this->sitemapService->defaultLanguage();
        if ($request->input()) {
            $records = $this->sitemapService->getLocations($request, $language);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.locations');
        $lastPage = $this->sitemapService->getLocationsLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function events(Request $request): Response
    {
        $language = $this->sitemapService->defaultLanguage();
        if ($request->input()) {
            $records = $this->sitemapService->getEvents($request, $language);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.events');
        $lastPage = $this->sitemapService->getEventsLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function contributors(Request $request): Response
    {
        $language = $this->sitemapService->defaultLanguage();
        if ($request->input()) {
            $records = $this->sitemapService->getContributors($request, $language);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.contributors');
        $lastPage = $this->sitemapService->getContributorsLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function latestNews(Request $request): Response
    {
        $language = $this->sitemapService->defaultLanguage();
        $records  = $this->sitemapService->latestNews($language);

        return response()->view('sitemaps.news', compact('records'));
    }

    public function news(Request $request): Response
    {
        $language = $this->sitemapService->defaultLanguage();
        if ($request->input()) {
            $records = $this->sitemapService->getNews($request, $language);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = route('sitemaps.news');
        $lastPage = $this->sitemapService->getNewsLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function categoryNews(Request $request, string $slugTree): Response
    {
        $language = $this->sitemapService->defaultLanguage();
        $category = $this->sitemapService->categoryBySlugTree($language, $slugTree);

        if ($request->input()) {
            $records = $this->sitemapService->getCategoryNews($request, $language, $category);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $category->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getCategoryNewsLastPageNo($request, $language, $category);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }

    public function tagNews(Request $request, string $slug): Response
    {
        $language = $this->sitemapService->defaultLanguage();
        $tag      = $this->sitemapService->tag($language, $slug);

        if ($request->input()) {
            $records = $this->sitemapService->getTagNews($request, $language, $tag);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $tag->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getTagNewsLastPageNo($request, $language, $tag);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }

    public function locationNews(Request $request, string $slugTree): Response
    {
        $language = $this->sitemapService->defaultLanguage();
        $location = $this->sitemapService->locationBySlugTree($language, $slugTree);

        if ($request->input()) {
            $records = $this->sitemapService->getLocationNews($request, $language, $location);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $location->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getLocationNewsLastPageNo($request, $language, $location);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }

    public function eventNews(Request $request, string $slug): Response
    {
        $language = $this->sitemapService->defaultLanguage();
        $event    = $this->sitemapService->event($language, $slug);

        if ($request->input()) {
            $records = $this->sitemapService->getEventNews($request, $language, $event);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $event->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getEventNewsLastPageNo($request, $language, $event);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function contributorNews(Request $request, string $slug): Response
    {
        $language    = $this->sitemapService->defaultLanguage();
        $contributor = $this->sitemapService->contributor($language, $slug);

        if ($request->input()) {
            $records = $this->sitemapService->getContributorNews($request, $language, $contributor);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $contributor->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getContributorNewsLastPageNo($request, $language, $contributor);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }

    public function localizedIndex(Request $request, string $languageCode): Response
    {
        $language = $this->sitemapService->language($languageCode);
        $pages    = $this->sitemapService->getPages($request, $language);

        return response()->view('sitemaps.index', compact('pages'));
    }

    public function localizedCategories(Request $request, string $languageCode): Response
    {
        $language = $this->sitemapService->language($languageCode);
        if ($request->input()) {
            $records = $this->sitemapService->getCategories($request, $language);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.categories');

        if (!$language->is_default) {
            $routeUrl = route('sitemaps.localized.categories');
        }
        $lastPage = $this->sitemapService->getCategoriesLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function localizedTags(Request $request, string $languageCode): Response
    {
        $language = $this->sitemapService->language($languageCode);
        if ($request->input()) {
            $records = $this->sitemapService->getTags($request, $language);

            return response()->view('sitemaps.attributes', compact('records'));
        }
        $routeUrl = route('sitemaps.tags');
        if (! $language->is_default) {
            $routeUrl = route('sitemaps.localized.tags');
        }
        $lastPage = $this->sitemapService->getTagsLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function localizedLocations(Request $request, string $languageCode): Response
    {
        $language = $this->sitemapService->language($languageCode);
        if ($request->input()) {
            $records = $this->sitemapService->getLocations($request, $language);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.locations');
        if (! $language->is_default) {
            $routeUrl = route('sitemaps.localized.locations');
        }
        $lastPage = $this->sitemapService->getLocationsLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function localizedEvents(Request $request, string $languageCode): Response
    {
        $language = $this->sitemapService->language($languageCode);
        if ($request->input()) {
            $records = $this->sitemapService->getEvents($request, $language);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.events');
        if (! $language->is_default) {
            $routeUrl = route('sitemaps.localized.events');
        }
        $lastPage = $this->sitemapService->getEventsLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function localizedContributors(Request $request, string $languageCode): Response
    {
        $language = $this->sitemapService->language($languageCode);
        if ($request->input()) {
            $records = $this->sitemapService->getContributors($request, $language);

            return response()->view('sitemaps.attributes', compact('records'));
        }

        $routeUrl = route('sitemaps.contributors');
        if (! $language->is_default) {
            $routeUrl = route('sitemaps.localized.contributors');
        }
        $lastPage = $this->sitemapService->getContributorsLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function localizedLatestNews(Request $request, string $languageCode): Response
    {
        $language = $this->sitemapService->language($languageCode);
        $records  = $this->sitemapService->latestNews($language);

        return response()->view('sitemaps.news', compact('records'));
    }

    public function localizedNews(Request $request, string $languageCode): Response
    {
        $language = $this->sitemapService->language($languageCode);
        if ($request->input()) {
            $records = $this->sitemapService->getNews($request, $language);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = route('sitemaps.news');
        if (! $language->is_default) {
            $routeUrl = route('sitemaps.localized.news');
        }
        $lastPage = $this->sitemapService->getNewsLastPageNo($language);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function localizedCategoryNews(Request $request, string $languageCode, string $slugTree): Response
    {
        $language = $this->sitemapService->language($languageCode);
        $category = $this->sitemapService->categoryBySlugTree($language,$slugTree);

        if ($request->input()) {
            $records = $this->sitemapService->getCategoryNews($request, $language, $category);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $category->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getCategoryNewsLastPageNo($request, $language, $category);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }

    public function localizedTagNews(Request $request, string $languageCode, string $slug): Response
    {
        $language = $this->sitemapService->language($languageCode);
        $tag      = $this->sitemapService->tag($language,$slug);

        if ($request->input()) {
            $records = $this->sitemapService->getTagNews($request, $language, $tag);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $tag->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getTagNewsLastPageNo($request, $language, $tag);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }

    public function localizedLocationNews(Request $request, string $languageCode, string $slugTree): Response
    {
        $language = $this->sitemapService->language($languageCode);
        $location = $this->sitemapService->locationBySlugTree($language,$slugTree);

        if ($request->input()) {
            $records = $this->sitemapService->getLocationNews($request, $language, $location);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $location->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getLocationNewsLastPageNo($request, $language, $location);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }

    public function localizedEventNews(Request $request, string $languageCode, string $slug): Response
    {
        $language = $this->sitemapService->language($languageCode);
        $event    = $this->sitemapService->event($language,$slug);

        if ($request->input()) {
            $records = $this->sitemapService->getEventNews($request, $language, $event);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $event->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getEventNewsLastPageNo($request, $language, $event);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));
    }

    public function localizedContributorNews(Request $request, string $languageCode, string $slug): Response
    {
        $language    = $this->sitemapService->language($languageCode);
        $contributor = $this->sitemapService->contributor($language,$slug);

        if ($request->input()) {
            $records = $this->sitemapService->getContributorNews($request, $language, $contributor);

            return response()->view('sitemaps.news', compact('records'));
        }

        $routeUrl = $contributor->getSitemapUrlAttribute();

        $lastPage = $this->sitemapService->getContributorNewsLastPageNo($request, $language, $contributor);

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeUrl'));

    }
}
