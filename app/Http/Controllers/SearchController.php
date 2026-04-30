<?php
namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
        $this->middleware(['auth', 'verified'])->only(['user']);
    }

    public function genders(Request $request)
    {
        return response()->json($this->searchService->genders($request));
    }

    public function perPages(Request $request)
    {
        return response()->json($this->searchService->perPages($request));
    }

    public function religions(Request $request)
    {
        return response()->json($this->searchService->religions($request));
    }

    public function maritalStatuses(Request $request)
    {
        return response()->json($this->searchService->maritalStatuses($request));
    }

    public function activityLogEvents(Request $request)
    {
        return response()->json($this->searchService->activityLogEvents($request));
    }

    public function activityLogSubjectTypes(Request $request)
    {
        return response()->json($this->searchService->activityLogSubjectTypes($request));
    }

    public function homePageSectionCategories(Request $request)
    {
        return response()->json($this->searchService->homePageSectionCategories($request));
    }

    public function users(Request $request)
    {
        return response()->json($this->searchService->users($request));
    }

    public function userRoles(Request $request)
    {
        return response()->json($this->searchService->userRoles($request));
    }

    public function languages(Request $request)
    {
        return response()->json($this->searchService->languages($request));
    }

    public function categories(Request $request)
    {
        return response()->json($this->searchService->categories($request));
    }

    public function tags(Request $request)
    {
        return response()->json($this->searchService->tags($request));
    }

    public function locations(Request $request)
    {
        return response()->json($this->searchService->locations($request));
    }

    public function events(Request $request)
    {
        return response()->json($this->searchService->events($request));
    }

    public function authors(Request $request)
    {
        return response()->json($this->searchService->authors($request));
    }

    public function medias(Request $request)
    {
        return response()->json($this->searchService->medias($request));
    }

    public function categoryTree(Request $request)
    {
        return response()->json($this->searchService->categoryTree($request));
    }

    public function locationTree(Request $request)
    {
        return response()->json($this->searchService->locationTree($request));
    }

    public function user(string | int $slugOrId)
    {
        return response()->json($this->searchService->user($slugOrId));
    }

    public function userRole(string | int $slugOrId)
    {
        return response()->json($this->searchService->userRole($slugOrId));
    }

    public function language(string | int $slugOrId)
    {
        return response()->json($this->searchService->language($slugOrId));
    }

    public function category(string | int $slugOrId)
    {
        return response()->json($this->searchService->category($slugOrId));
    }

    public function tag(string | int $slugOrId)
    {
        return response()->json($this->searchService->tag($slugOrId));
    }

    public function location(string | int $slugOrId)
    {
        return response()->json($this->searchService->location($slugOrId));
    }

    public function event(string | int $slugOrId)
    {
        return response()->json($this->searchService->event($slugOrId));
    }

    public function author(string | int $slugOrId)
    {
        return response()->json($this->searchService->author($slugOrId));
    }
}
