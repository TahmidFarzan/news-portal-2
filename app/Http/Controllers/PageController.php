<?php
namespace App\Http\Controllers;

use App\Services\PageService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PageController extends Controller
{
    protected PageService $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    public function home()
    {
        $page         = $this->pageService->homePage();
        $leadNews     = $this->pageService->homeLeadNews();
        $recentNews   = $this->pageService->recentNews();
        $topEvents    = $this->pageService->homeTopEvents();
        $bottomEvents = $this->pageService->homeBottomEvents();
        $trends       = $this->pageService->homeTrends();
        $surveys      = $this->pageService->homeSurveys();

        return Inertia::render('Home', [
            'page'         => $page,
            'leadNews'     => $leadNews,
            "recentNews"   => $recentNews,
            "topEvents"    => $topEvents,
            "bottomEvents" => $bottomEvents,
            "trends"       => $trends,
            "surveys"      => $surveys,
        ]);
    }

    public function homeEventNews(string $slug)
    {
        $event = $this->pageService->event($slug);
        $news  = $this->pageService->homeEventNews($event);

        return response()->json($news);
    }

    public function homeNewsTypeNews(string $slug)
    {
        $newsType = $this->pageService->newsType($slug);
        $news     = $this->pageService->homeNewsTypeNews($newsType);
        return response()->json($news);
    }

    public function homeCategoryNews(Request $request, int | string $idOrSlug)
    {
        $category = $this->pageService->homeCategoryByIdOrSlug($idOrSlug);
        $news     = $this->pageService->homeCategoryNews($request, $category);
        return response()->json($news);
    }

    public function homeCategory(int | string $idOrSlug)
    {
        $category = $this->pageService->homeCategoryByIdOrSlug($idOrSlug);
        return response()->json($category);
    }

    public function homeSurveys()
    {
        $category = $this->pageService->homeSurveys();
        return response()->json($category);
    }

    public function latest(Request $request)
    {
        $news = $this->pageService->newsSearch($request);
        $page = $this->pageService->latestPage();

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

    public function search(Request $request)
    {
        $news     = $this->pageService->newsSearch($request);
        $language = $this->pageService->language();
        $page     = $this->pageService->searchPage();

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('Search', [
            "language" => $language,
            'news'     => $news,
            "page"     => $page,
        ]);
    }

    public function videos(Request $request)
    {
        $newsType = $this->pageService->newsType("video");
        $news     = $this->pageService->newsTypeNews($request, $newsType);

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

    public function imageGalleries(Request $request)
    {
        $newsType = $this->pageService->newsType("image-gallery");
        $news     = $this->pageService->newsTypeNews($request, $newsType);

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

    public function page(string $slugTree)
    {
        $page = $this->pageService->page($slugTree);

        return Inertia::render('Page', [
            "page" => $page,
        ]);
    }

    public function newsDetails(string $slug)
    {
        $news = $this->pageService->news($slug);
        return Inertia::render('NewsDetails', [
            'news' => $news,
        ]);
    }

    public function tagNews(Request $request, string $slug)
    {
        $tag  = $this->pageService->tag($slug);
        $news = $this->pageService->tagNews($request, $tag);

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

    public function contributorNews(Request $request, string $slug)
    {
        $contributor = $this->pageService->contributor($slug);
        $news        = $this->pageService->contributorNews($request, $contributor);

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

    public function eventNews(Request $request, string $slug)
    {
        $event = $this->pageService->event($slug);
        $news  = $this->pageService->eventNews($request, $event);

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

    public function categoryNews(Request $request, string $slugTree)
    {
        $category = $this->pageService->category($slugTree);
        $news     = $this->pageService->categoryNews($request, $category);

        $pageSectionNews = $this->pageService->categoryNewsPlacement($category);
        $recentNews      = $this->pageService->recentNews();

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('CategoryNews', [
            'category'        => $category,
            'news'            => $news,
            "recentNews"      => $recentNews,
            "pageSectionNews" => $pageSectionNews,
        ]);
    }

    public function locationNews(Request $request, string $slugTree)
    {

        $location = $this->pageService->location($slugTree);
        $news     = $this->pageService->locationNews($request, $location);

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

    public function categoryLocationMaxDepthAndLevel(string $slugTree)
    {
        $category                         = $this->pageService->category($slugTree);
        $categoryLocationMaxDepthAndLevel = $this->pageService->categoryLocationMaxDepthAndLevel($category);
        return response()->json($categoryLocationMaxDepthAndLevel);
    }

    public function homeSurveySurveyQuestionSubmit(Request $request, string $slug, string $surveyQuestionSlug)
    {
        $survey         = $this->pageService->homeSurvey($slug);
        $surveyQuestion = $this->pageService->homeSurveyQuestion($survey, $surveyQuestionSlug);
        $result         = $this->pageService->homeSurveySurveyQuestionSubmit($request, $survey, $surveyQuestion);

        return response()->json($result);
    }
}
