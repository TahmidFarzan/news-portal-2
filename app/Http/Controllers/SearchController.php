<?php
namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SearchController extends Controller
{
    protected SearchService $searchService;

    private int $longCacheLimitSecond = 3600;
    private int $frequentlyCacheLimitInSecond = 60;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
        $this->middleware(['auth', 'verified'])->only(['user']);
    }


    private function jsonResponse(mixed $data, int $seconds): JsonResponse
    {
        return response()
            ->json($data)
            ->header('Cache-Control', 'public, max-age=' . $seconds);
    }

    public function genders(Request $request): JsonResponse
    {
        return $this->jsonResponse($this->searchService->genders($request), $this->longCacheLimitSecond);
    }

    public function perPages(Request $request): JsonResponse
    {
        return $this->jsonResponse($this->searchService->perPages($request), $this->longCacheLimitSecond);
    }

    public function religions(Request $request): JsonResponse
    {
        return $this->jsonResponse($this->searchService->religions($request), $this->longCacheLimitSecond);
    }

    public function maritalStatuses(Request $request): JsonResponse
    {
        return $this->jsonResponse($this->searchService->maritalStatuses($request), $this->longCacheLimitSecond);
    }

    public function activityLogEvents(Request $request): JsonResponse
    {
        return $this->jsonResponse($this->searchService->activityLogEvents($request), $this->longCacheLimitSecond);
    }

    public function activityLogSubjectTypes(Request $request): JsonResponse
    {
        return $this->jsonResponse($this->searchService->activityLogSubjectTypes($request), $this->longCacheLimitSecond);
    }

    public function newsTypes(Request $request)
    {
        return $this->jsonResponse($this->searchService->newsTypes($request), $this->longCacheLimitSecond);
    }

    public function pageSections(Request $request)
    {
        return $this->jsonResponse($this->searchService->pageSections($request), $this->longCacheLimitSecond);
    }

    public function users(Request $request)
    {
        return $this->jsonResponse($this->searchService->users($request), $this->frequentlyCacheLimitInSecond);
    }

    public function userRoles(Request $request)
    {
        return $this->jsonResponse($this->searchService->userRoles($request), $this->longCacheLimitSecond);
    }

    public function languages(Request $request)
    {
        return $this->jsonResponse($this->searchService->languages($request), $this->frequentlyCacheLimitInSecond);
    }

    public function categories(Request $request)
    {
        return $this->jsonResponse($this->searchService->categories($request), $this->frequentlyCacheLimitInSecond);
    }

    public function tags(Request $request)
    {
        return $this->jsonResponse($this->searchService->tags($request), $this->frequentlyCacheLimitInSecond);
    }

    public function locations(Request $request)
    {
        return $this->jsonResponse($this->searchService->locations($request), $this->frequentlyCacheLimitInSecond);
    }

    public function events(Request $request)
    {
        return $this->jsonResponse($this->searchService->events($request), $this->frequentlyCacheLimitInSecond);
    }

    public function contributors(Request $request)
    {
        return $this->jsonResponse($this->searchService->contributors($request), $this->frequentlyCacheLimitInSecond);
    }

    public function newses(Request $request)
    {
        return $this->jsonResponse($this->searchService->newses($request), $this->frequentlyCacheLimitInSecond);
    }

    public function medias(Request $request)
    {
        return $this->jsonResponse($this->searchService->medias($request), $this->frequentlyCacheLimitInSecond);
    }

    public function categoryTree(Request $request)
    {
        return $this->jsonResponse($this->searchService->categoryTree($request), $this->frequentlyCacheLimitInSecond);
    }

    public function locationTree(Request $request)
    {
        return $this->jsonResponse($this->searchService->locationTree($request), $this->frequentlyCacheLimitInSecond);
    }

    public function user(string | int $slugOrId)
    {
        return $this->jsonResponse($this->searchService->user($slugOrId), $this->frequentlyCacheLimitInSecond);
    }

    public function userRole(string | int $slugOrId)
    {
        return $this->jsonResponse($this->searchService->userRole($slugOrId), $this->frequentlyCacheLimitInSecond);
    }

    public function language(string | int $slugOrId)
    {
        return $this->jsonResponse($this->searchService->language($slugOrId), $this->frequentlyCacheLimitInSecond);
    }

    public function category(string | int $slugOrId)
    {
        return $this->jsonResponse($this->searchService->category($slugOrId), $this->frequentlyCacheLimitInSecond);
    }

    public function tag(string | int $slugOrId)
    {
        return $this->jsonResponse($this->searchService->tag($slugOrId), $this->frequentlyCacheLimitInSecond);
    }

    public function location(string | int $slugOrId)
    {
        return $this->jsonResponse($this->searchService->location($slugOrId), $this->frequentlyCacheLimitInSecond);
    }

    public function event(string | int $slugOrId)
    {
        return $this->jsonResponse($this->searchService->event($slugOrId), $this->frequentlyCacheLimitInSecond);
    }

    public function contributor(string | int $slugOrId)
    {
        return $this->jsonResponse($this->searchService->contributor($slugOrId), $this->frequentlyCacheLimitInSecond);
    }
}
