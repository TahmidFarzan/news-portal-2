<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BreakingNewsRequest;
use App\Services\BackOffice\BreakingNewsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class BreakingNewsController extends Controller
{
    protected BreakingNewsService $breakingNewsService;

    public function __construct(BreakingNewsService $breakingNewsService)
    {
        $this->breakingNewsService = $breakingNewsService;
        $this->middleware(['auth', 'verified', 'user.role.check:admin,news_desk']);
    }

    public function index(Request $request)
    {
        $breakingNews = $this->breakingNewsService->new();
        Gate::authorize('viewAny', $breakingNews);

        $breakingNewses = $this->breakingNewsService->search($request);

        return Inertia::render('back-office/breaking-news/Index', [
            'breakingNewses' => $breakingNewses,
        ]);
    }

    public function details(string $slug)
    {
        $breakingNews = $this->breakingNewsService->find($slug);
        $breakingNews = $this->breakingNewsService->loadRelations($breakingNews);

        Gate::authorize('view', $breakingNews);

        return Inertia::render('back-office/breaking-news/Details', [
            'breakingNews' => $breakingNews,
        ]);
    }

    public function create()
    {
        $breakingNews = $this->breakingNewsService->new();
        Gate::authorize('create', $breakingNews);

        return Inertia::render('back-office/breaking-news/Create', [
            'breakingNews' => $breakingNews,
        ]);
    }

    public function edit(string $slug)
    {
        $breakingNews = $this->breakingNewsService->find($slug);
        $breakingNews = $this->breakingNewsService->loadRelations($breakingNews);

        Gate::authorize('update', $breakingNews);

        return Inertia::render('back-office/breaking-news/Create', [
            'breakingNews' => $breakingNews,
        ]);
    }

    public function save(BreakingNewsRequest $request)
    {
        $breakingNews = $this->breakingNewsService->new();
        Gate::authorize('create', $breakingNews);

        $result = $this->breakingNewsService->save($request, $breakingNews);

        return to_route('back-office.breaking-newses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(BreakingNewsRequest $request, string $slug)
    {
        $breakingNews = $this->breakingNewsService->find($slug);

        Gate::authorize('update', $breakingNews);

        $result = $this->breakingNewsService->save($request, $breakingNews);

        return to_route('back-office.breaking-newses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function trash(string $slug)
    {
        $breakingNews = $this->breakingNewsService->find($slug);

        Gate::authorize('delete', $breakingNews);

        $result = $this->breakingNewsService->trash($breakingNews);

        return to_route('back-office.breaking-newses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function restore(string $slug)
    {
        $breakingNews = $this->breakingNewsService->find($slug);

        Gate::authorize('restore', $breakingNews);

        $result = $this->breakingNewsService->restore($breakingNews);

        return to_route('back-office.breaking-newses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
    {
        $breakingNews = $this->breakingNewsService->find($slug);

        Gate::authorize('forceDelete', $breakingNews);

        $result = $this->breakingNewsService->delete($breakingNews);

        return to_route('back-office.breaking-newses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
