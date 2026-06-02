<?php
namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
        $this->middleware(['auth', 'verified'])->only(['user']);
    }

    public function perPages(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->perPages($request)
        );
    }

    public function genders(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->genders($request)
        );
    }

    public function religions(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->religions($request)
        );
    }

    public function maritalStatuses(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->maritalStatuses($request)
        );
    }

    public function activityLogEvents(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->activityLogEvents($request)
        );
    }

    public function activityLogSubjectTypes(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->activityLogSubjectTypes($request)
        );
    }

    public function menuTypes(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->menuTypes($request)
        );
    }

    public function pageSections(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->pageSections($request)
        );
    }

    public function menuItemModels(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->menuItemModels($request)
        );
    }

    public function users(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->users($request)
        );
    }

    public function userRoles(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->userRoles($request)
        );
    }

    public function newsTypes(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->newsTypes($request)
        );
    }

    public function languages(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->languages($request)
        );
    }

    public function categories(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->categories($request)
        );
    }

    public function tags(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->tags($request)
        );
    }

    public function locations(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->locations($request)
        );
    }

    public function events(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->events($request)
        );
    }

    public function contributors(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->contributors($request)
        );
    }

    public function newses(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->newses($request)
        );
    }

    public function breakingNewses(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->breakingNewses($request)
        );
    }

    public function medias(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->medias($request)
        );
    }

    public function menuItems(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->menuItems($request)
        );
    }

    public function categoryTree(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->categoryTree($request)
        );
    }

    public function locationTree(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->locationTree($request)
        );
    }

    public function menuItemTree(Request $request): JsonResponse
    {
        return response()->json(
            $this->searchService->menuItemTree($request)
        );
    }

    public function user(string|int $slugOrId): JsonResponse
    {
        return response()->json(
            $this->searchService->user($slugOrId)
        );
    }

    public function userRole(string|int $slugOrId): JsonResponse
    {
        return response()->json(
            $this->searchService->userRole($slugOrId)
        );
    }

    public function newsType(string|int $slugOrId): JsonResponse
    {
        return response()->json(
            $this->searchService->newsType($slugOrId)
        );
    }

    public function language(string|int $slugOrId): JsonResponse
    {
        return response()->json(
            $this->searchService->language($slugOrId)
        );
    }

    public function category(string|int $slugOrId): JsonResponse
    {
        return response()->json(
            $this->searchService->category($slugOrId)
        );
    }

    public function tag(string|int $slugOrId): JsonResponse
    {
        return response()->json(
            $this->searchService->tag($slugOrId)
        );
    }

    public function location(string|int $slugOrId): JsonResponse
    {
        return response()->json(
            $this->searchService->location($slugOrId)
        );
    }

    public function event(string|int $slugOrId): JsonResponse
    {
        return response()->json(
            $this->searchService->event($slugOrId)
        );
    }

    public function contributor(string|int $slugOrId): JsonResponse
    {
        return response()->json(
            $this->searchService->contributor($slugOrId)
        );
    }

    public function menuItem(string|int $slugOrId): JsonResponse
    {
        return response()->json(
            $this->searchService->menuItem($slugOrId)
        );
    }
}
