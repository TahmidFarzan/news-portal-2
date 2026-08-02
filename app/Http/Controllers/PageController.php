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

    public function home(): InertiaResponse
    {
        $language = $this->pageService->defaultLanguage();

        $page         = $this->pageService->homePage($language);
        $leadNews     = $this->pageService->homeLeadNews($language);
        $recentNews   = $this->pageService->recentNewsSidebar($language);
        $popularNews  = $this->pageService->popularNewsSidebar($language);
        $topEvents    = $this->pageService->homeTopEvents($language);
        $bottomEvents = $this->pageService->homeBottomEvents($language);
        $trends       = $this->pageService->homeTrends($language);

        return Inertia::render('Home', [
            'page'         => $page,
            'leadNews'     => $leadNews,
            'recentNews'   => $recentNews,
            'popularNews'  => $popularNews,
            'topEvents'    => $topEvents,
            'bottomEvents' => $bottomEvents,
            'trends'       => $trends,
        ]);
    }

    public function homeEventNews(string $slug): JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $event = $this->pageService->event($language, $slug);
        $news  = $this->pageService->homeEventNews($event, $language);

        return response()->json($news);
    }

    public function homeNewsTypeNews(string $slug): JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $newsType = $this->pageService->newsType($slug);
        $news     = $this->pageService->homeNewsTypeNews($newsType, $language);

        return response()->json($news);
    }

    public function homeCategoryNews(Request $request, int | string $slug, ): JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $category = $this->pageService->homeCategoryBySlug($language, $slug);
        $news     = $this->pageService->homeCategoryNews($request, $language, $category);

        return response()->json($news);
    }

    public function homeCategory(int | string $slug, ): JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $category = $this->pageService->homeCategoryBySlug($language, $slug);

        return response()->json($category);
    }

    public function homeSurveys(): JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $category = $this->pageService->homeSurveys($language);

        return response()->json($category);
    }

    public function latest(Request $request): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

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

    public function search(Request $request): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $news     = $this->pageService->newsSearch($request, $language);
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

    public function videos(Request $request): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

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

    public function imageGalleries(Request $request): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

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

    public function page(string $slugTree): InertiaResponse
    {
        $language = $this->pageService->defaultLanguage();

        $page = $this->pageService->page($language, $slugTree);

        return Inertia::render('Page', [
            'page' => $page,
        ]);
    }

    public function newsDetails(string $slug): InertiaResponse
    {
        $language = $this->pageService->defaultLanguage();
        $news     = $this->pageService->news($language, $slug);

        $this->pageService->newsHitCounterCalculate($news);

        return Inertia::render('NewsDetails', [
            'news' => $news,
        ]);
    }

    public function tagNews(Request $request, string $slug): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $tag  = $this->pageService->tag($language, $slug);
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

    public function contributorNews(Request $request, string $slug): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $contributor = $this->pageService->contributor($language, $slug);
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

    public function eventNews(Request $request, string $slug): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $event = $this->pageService->event($language, $slug);
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

    public function categoryNews(Request $request, string $slugTree): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $category = $this->pageService->category($language, $slugTree);
        $news     = $this->pageService->categoryNews($request, $language, $category);

        $pageSectionNews = $this->pageService->categoryNewsPlacement($language, $category);

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

    public function locationNews(Request $request, string $slugTree): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $location = $this->pageService->location($language, $slugTree);
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

    public function categoryLocationMaxDepthAndLevel(string $slugTree): JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $category                         = $this->pageService->category($language, $slugTree);
        $categoryLocationMaxDepthAndLevel = $this->pageService->categoryLocationMaxDepthAndLevel($language, $category);

        return response()->json($categoryLocationMaxDepthAndLevel);
    }

    public function homeSurveySurveyQuestionSubmit(Request $request, string $slug, string $surveyQuestionSlug): JsonResponse
    {
        $language = $this->pageService->defaultLanguage();

        $survey         = $this->pageService->homeSurvey($language, $slug);
        $surveyQuestion = $this->pageService->homeSurveyQuestion($language, $survey, $surveyQuestionSlug);
        $result         = $this->pageService->homeSurveySurveyQuestionSubmit($request, $survey, $surveyQuestion);

        return response()->json($result);
    }

    public function localizedHome(string $languageCode): InertiaResponse
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

    public function localizedHomeEventNews(string $languageCode, string $slug): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $event = $this->pageService->event($language, $slug);
        $news  = $this->pageService->homeEventNews($event, $language);

        return response()->json($news);
    }

    public function localizedHomeNewsTypeNews(string $languageCode, string $slug): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $newsType = $this->pageService->newsType($slug);
        $news     = $this->pageService->homeNewsTypeNews($newsType, $language);

        return response()->json($news);
    }

    public function localizedHomeCategoryNews(Request $request, string $languageCode, string $slug): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $category = $this->pageService->homeCategoryBySlug($language, $slug);
        $news     = $this->pageService->homeCategoryNews($request, $language, $category);

        return response()->json($news);
    }

    public function localizedHomeCategory(string $languageCode, string $slug): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $category = $this->pageService->homeCategoryBySlug($language, $slug);

        return response()->json($category);
    }

    public function localizedHomeSurveys(string $languageCode): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $category = $this->pageService->homeSurveys($language);

        return response()->json($category);
    }

    public function localizedLatest(Request $request, string $languageCode): InertiaResponse | JsonResponse
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

    public function localizedSearch(Request $request, string $languageCode): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $news     = $this->pageService->newsSearch($request, $language);
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

    public function localizedVideos(Request $request, string $languageCode): InertiaResponse | JsonResponse
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

    public function localizedImageGalleries(Request $request, string $languageCode): InertiaResponse | JsonResponse
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

    public function localizedPage(string $languageCode, string $slugTree): InertiaResponse
    {
        $language = $this->pageService->language($languageCode);

        $page = $this->pageService->page($language, $slugTree);

        return Inertia::render('Page', [
            'page' => $page,
        ]);
    }

    public function localizedNewsDetails(string $languageCode, string $slug): InertiaResponse
    {
        $language = $this->pageService->language($languageCode);
        $news     = $this->pageService->news($language, $slug);

        $this->pageService->newsHitCounterCalculate($news);

        return Inertia::render('NewsDetails', [
            'news' => $news,
        ]);
    }

    public function localizedTagNews(Request $request, string $languageCode, string $slug): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $tag  = $this->pageService->tag($language, $slug);
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

    public function localizedContributorNews(Request $request, string $languageCode, string $slug): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $contributor = $this->pageService->contributor($language, $slug);
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

    public function localizedEventNews(Request $request, string $languageCode, string $slug): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $event = $this->pageService->event($language, $slug);
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

    public function localizedCategoryNews(Request $request, string $languageCode, string $slugTree): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $category = $this->pageService->category($language, $slugTree);
        $news     = $this->pageService->categoryNews($request, $language, $category);

        $pageSectionNews = $this->pageService->categoryNewsPlacement($language, $category);

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

    public function localizedLocationNews(Request $request, string $languageCode, string $slugTree): InertiaResponse | JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $location = $this->pageService->location($language, $slugTree);
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

    public function localizedCategoryLocationMaxDepthAndLevel(string $languageCode, string $slugTree): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $category                         = $this->pageService->category($language, $slugTree);
        $categoryLocationMaxDepthAndLevel = $this->pageService->categoryLocationMaxDepthAndLevel($language, $category);

        return response()->json($categoryLocationMaxDepthAndLevel);
    }

    public function localizedHomeSurveySurveyQuestionSubmit(Request $request, string $languageCode, string $slug, string $surveyQuestionSlug): JsonResponse
    {
        $language = $this->pageService->language($languageCode);

        $survey         = $this->pageService->homeSurvey($language, $slug);
        $surveyQuestion = $this->pageService->homeSurveyQuestion($language, $survey,  $surveyQuestionSlug);
        $result         = $this->pageService->homeSurveySurveyQuestionSubmit($request, $survey, $surveyQuestion);

        return response()->json($result);
    }
}
