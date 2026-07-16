<?php
namespace App\Http\Controllers;

use App\Services\PageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class PageController extends Controller
{
    protected PageService $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    public function home(string | null $languageCode = null): InertiaResponse
    {
        $language = $this->pageService->language($languageCode);

        $page         = $this->pageService->homePage($language);
        $leadNews     = $this->pageService->homeLeadNews($language);
        $recentNews   = $this->pageService->recentNewsSidebar($language);
        $popularNews  = $this->pageService->popularNewsSidebar($language);
        $topEvents    = $this->pageService->homeTopEvents($language);
        $bottomEvents = $this->pageService->homeBottomEvents($language);
        $trends       = $this->pageService->homeTrends($language);
        $surveys      = $this->pageService->homeSurveys($language);

        return Inertia::render('Home', [
            'page'         => $page,
            'leadNews'     => $leadNews,
            'recentNews'   => $recentNews,
            'popularNews'  => $popularNews,
            'topEvents'    => $topEvents,
            'bottomEvents' => $bottomEvents,
            'trends'       => $trends,
            'surveys'      => $surveys,
        ]);
    }

    public function homeEventNews(string $slug, string | null $languageCode = null): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $event = $this->pageService->event($slug, $language);
        $news  = $this->pageService->homeEventNews($event, $language);

        return response()->json($news);
    }

    public function homeNewsTypeNews(string $slug, string | null $languageCode = null): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $newsType = $this->pageService->newsType($slug, $language);
        $news     = $this->pageService->homeNewsTypeNews($newsType, $language);

        return response()->json($news);
    }

    public function homeCategoryNews(Request $request, int | string $slug, string | null $languageCode = null): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $category = $this->pageService->homeCategoryBySlug($slug, $language);
        $news     = $this->pageService->homeCategoryNews($request, $category, $language);

        return response()->json($news);
    }

    public function homeCategory(int | string $slug, string | null $languageCode = null): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $category = $this->pageService->homeCategoryBySlug($slug, $language);

        return response()->json($category);
    }

    public function homeSurveys(string | null $languageCode = null): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $category = $this->pageService->homeSurveys($language);

        return response()->json($category);
    }

    public function latest(Request $request, string | null $languageCode = null): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $news = $this->pageService->recentNews($language);
        $page = $this->pageService->latestPage($language);

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('Latest', [
            'news' => $news,
            'page' => $page,
        ]);
    }

    public function search(Request $request, string | null $languageCode = null): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $news     = $this->pageService->newsSearch($request, $language);
        $language = $this->pageService->language($language);
        $page     = $this->pageService->searchPage($language);

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('Search', [
            'language' => $language,
            'news'     => $news,
            'page'     => $page,
        ]);
    }

    public function videos(Request $request, string | null $languageCode = null): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $newsType = $this->pageService->newsType('video');
        $news     = $this->pageService->newsTypeNews($request, $newsType, $language);

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('VideoNews', [
            'newsTypes' => $newsType,
            'news'      => $news,
        ]);
    }

    public function imageGalleries(Request $request, string | null $languageCode = null): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $newsType = $this->pageService->newsType('image-gallery');
        $news     = $this->pageService->newsTypeNews($request, $newsType, $language);

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('ImagesGalleryNews', [
            'newsTypes' => $newsType,
            'news'      => $news,
        ]);
    }

    public function page(string $slugTree, string | null $languageCode = null): InertiaResponse
    {
        $language = $this->pageService->language($languageCode);

        $page = $this->pageService->page($slugTree, $language);

        return Inertia::render('Page', [
            'page' => $page,
        ]);
    }

    public function newsDetails(string $slug, string | null $languageCode = null): InertiaResponse
    {
        $language = $this->pageService->language($languageCode);
        $news     = $this->pageService->news($slug, $language);

        $this->pageService->newsHitCounterCalculate($news);

        return Inertia::render('NewsDetails', [
            'news' => $news,
        ]);
    }

    public function tagNews(Request $request, string $slug, string | null $languageCode = null): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $tag  = $this->pageService->tag($slug, $language);
        $news = $this->pageService->tagNews($request, $tag, $language);

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('TagNews', [
            'tag'  => $tag,
            'news' => $news,
        ]);
    }

    public function contributorNews(Request $request, string $slug, string | null $languageCode = null): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $contributor = $this->pageService->contributor($slug, $language);
        $news        = $this->pageService->contributorNews($request, $contributor, $language);

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('ContributorNews', [
            'contributor' => $contributor,
            'news'        => $news,
        ]);
    }

    public function eventNews(Request $request, string $slug, string | null $languageCode = null): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $event = $this->pageService->event($slug, $language);
        $news  = $this->pageService->eventNews($request, $event, $language);

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('EventNews', [
            'event' => $event,
            'news'  => $news,
        ]);
    }

    public function categoryNews(Request $request, string $slugTree, string | null $languageCode = null): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $category = $this->pageService->category($slugTree, $language);
        $news     = $this->pageService->categoryNews($request, $category, $language);

        $pageSectionNews = $this->pageService->categoryNewsPlacement($category, $language);

        $recentNews  = $this->pageService->recentNewsSidebar($language);
        $popularNews = $this->pageService->popularNewsSidebar($language);

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('CategoryNews', [
            'category'        => $category,
            'news'            => $news,
            'recentNews'      => $recentNews,
            'popularNews'     => $popularNews,
            'pageSectionNews' => $pageSectionNews,
        ]);
    }

    public function locationNews(Request $request, string $slugTree, string | null $languageCode = null): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $location = $this->pageService->location($slugTree, $language);
        $news     = $this->pageService->locationNews($request, $location, $language);

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('LocationNews', [
            'location' => $location,
            'news'     => $news,
        ]);
    }

    public function categoryLocationMaxDepthAndLevel(string $slugTree, string | null $languageCode = null): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $category                         = $this->pageService->category($slugTree, $language);
        $categoryLocationMaxDepthAndLevel = $this->pageService->categoryLocationMaxDepthAndLevel($category, $language);

        return response()->json($categoryLocationMaxDepthAndLevel);
    }

    public function homeSurveySurveyQuestionSubmit(Request $request, string $slug, string $surveyQuestionSlug, string | null $languageCode = null): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $survey         = $this->pageService->homeSurvey($slug, $language);
        $surveyQuestion = $this->pageService->homeSurveyQuestion($survey, $surveyQuestionSlug, $language);
        $result         = $this->pageService->homeSurveySurveyQuestionSubmit($request, $survey, $surveyQuestion);

        return response()->json($result);
    }
}
