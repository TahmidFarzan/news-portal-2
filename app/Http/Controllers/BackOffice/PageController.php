<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use App\Services\BackOffice\PageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PageController extends Controller
{
    protected PageService $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
        $this->middleware(['auth', 'verified', 'user.role.check:admin']);
    }

    public function index(Request $request)
    {
        $page = $this->pageService->new();
        Gate::authorize('viewAny', $page);

        $pages = $this->pageService->search($request);

        return Inertia::render('back-office/pages/Index', [
            'pages' => $pages,
        ]);
    }

    public function details(string $slug)
    {
        $page = $this->pageService->find($slug);
        $page = $this->pageService->loadRelations($page);

        Gate::authorize('view', $page);

        return Inertia::render('back-office/pages/Details', [
            'page' => $page,
        ]);
    }

    public function create()
    {
        $page = $this->pageService->new();
        Gate::authorize('create', $page);

        return Inertia::render('back-office/pages/Create', [
            'page' => $page,
        ]);
    }

    public function edit(string $slug)
    {
        $page = $this->pageService->find($slug);
        $page = $this->pageService->loadRelations($page);

        Gate::authorize('update', $page);

        return Inertia::render('back-office/pages/Create', [
            'page' => $page,
        ]);
    }

    public function save(PageRequest $request)
    {
        $page = $this->pageService->new();

        Gate::authorize('create', $page);

        $result = $this->pageService->save($request, $page);

        return to_route('back-office.pages.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(PageRequest $request, string $slug)
    {
        $page = $this->pageService->find($slug);

        Gate::authorize('update', $page);

        $result = $this->pageService->save($request, $page);

        return to_route('back-office.pages.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function trash(string $slug)
    {
        $page = $this->pageService->find($slug);

        Gate::authorize('delete', $page);

        $result = $this->pageService->trash($page);

        return to_route('back-office.pages.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function restore(string $slug)
    {
        $page = $this->pageService->find($slug);

        Gate::authorize('restore', $page);

        $result = $this->pageService->restore($page);

        return to_route('back-office.pages.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
    {
        $page = $this->pageService->find($slug);

        Gate::authorize('forceDelete', $page);

        $result = $this->pageService->delete($page);

        return to_route('back-office.pages.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

}
