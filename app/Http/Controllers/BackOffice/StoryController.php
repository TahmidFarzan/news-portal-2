<?php

namespace App\Http\Controllers\BackOffice;

use App\Services\BackOffice\StoryService;
use App\Http\Requests\StoryRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class StoryController extends Controller
{
    protected StoryService $storyService;

    public function __construct(StoryService $storyService)
    {
        $this->storyService = $storyService;
        $this->middleware(['auth', 'verified', 'user.role:admin,news_desk']);
    }

    public function index(Request $request)
    {
        $story = $this->storyService->new();
        Gate::authorize('viewAny', $story);

        $stories = $this->storyService->search($request);

        return Inertia::render('back-office/stories/Index', [
            'stories' => $stories,
        ]);
    }

    public function details(string $slug)
    {
        $story = $this->storyService->find($slug);
        $story = $this->storyService->loadRelations($story);

        Gate::authorize('create', $story);

        return Inertia::render('back-office/stories/Details', [
            'story' => $story,
        ]);
    }

    public function create()
    {
        $story = $this->storyService->new();
        Gate::authorize('create', $story);

        return Inertia::render('back-office/stories/Create', [
            'story' => $story,
        ]);
    }

    public function edit(string $slug)
    {
        $story = $this->storyService->find($slug);
        $story = $this->storyService->loadRelations($story);

        Gate::authorize('update', $story);

        return Inertia::render('back-office/stories/Create', [
            'story' => $story,
        ]);
    }

    public function save(StoryRequest $request)
    {
        $story = $this->storyService->new();
        Gate::authorize('create', $story);

        $result = $this->storyService->save($request, $story);

        return to_route('back-office.stories.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }

    public function update(StoryRequest $request, string $slug)
    {
        $story = $this->storyService->find($slug);

        Gate::authorize('update', $story);

        $result = $this->storyService->save($request, $story);

        return to_route('back-office.stories.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }


    public function delete(string $slug)
    {
        $story = $this->storyService->find($slug);

        Gate::authorize('delete', $story);

        $result = $this->storyService->delete($story);

        return to_route('back-office.stories.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }

    public function restore(string $slug)
    {
        $story = $this->storyService->find($slug);

        Gate::authorize('update', $story);

        $result = $this->storyService->restore($story);

        return to_route('back-office.stories.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }
}
