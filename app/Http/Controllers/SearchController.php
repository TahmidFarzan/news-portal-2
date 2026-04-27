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
        $response = $this->searchService->genders($request);

        return response()->json($response);
    }

    public function perPages(Request $request)
    {
        $response = $this->searchService->perPages($request);

        return response()->json($response);
    }

    public function religions(Request $request)
    {
        $response = $this->searchService->religions($request);

        return response()->json($response);
    }

    public function maritalStatuses(Request $request)
    {
        $response = $this->searchService->maritalStatuses($request);

        return response()->json($response);
    }

    public function activityLogEvents(Request $request)
    {
        $response = $this->searchService->activityLogEvents($request);

        return response()->json($response);
    }

    public function activityLogSubjectTypes(Request $request)
    {
        $response = $this->searchService->activityLogSubjectTypes($request);

        return response()->json($response);
    }

    public function users(Request $request)
    {
        $response = $this->searchService->users($request);

        return response()->json($response);
    }

    public function userRoles(Request $request)
    {
        $response = $this->searchService->userRoles($request);

        return response()->json($response);
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
        $record = $this->searchService->user($slugOrId);

        return response()->json($record);
    }

    public function userRole(string | int $slugOrId)
    {
        $record = $this->searchService->userRole($slugOrId);

        return response()->json($record);
    }

    public function language($slugOrId)
    {
        $record = $this->searchService->language($slugOrId);
        return response()->json($record);
    }

    public function category($slugOrId)
    {
        $record = $this->searchService->category($slugOrId);
        return response()->json($record);
    }

    public function location($slugOrId)
    {
        $record = $this->searchService->locations($slugOrId);
        return response()->json($record);
    }
}
