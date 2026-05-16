<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\TagRequest;
use App\Services\BackOffice\TagService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TagController extends Controller
{
    protected TagService $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
        $this->middleware(['auth', 'verified', 'user.role.check:admin,news_desk']);
    }

    public function index(Request $request)
    {
        $tag = $this->tagService->new();
        Gate::authorize('viewAny', $tag);

        $tags = $this->tagService->search($request);

        return Inertia::render('back-office/tags/Index', [
            'tags' => $tags,
        ]);
    }

    public function details(string $slug)
    {
        $tag = $this->tagService->find($slug);
        $tag = $this->tagService->loadRelations($tag);

        Gate::authorize('view', $tag);

        return Inertia::render('back-office/tags/Details', [
            'tag' => $tag,
        ]);
    }

    public function create()
    {
        $tag = $this->tagService->new();
        Gate::authorize('create', $tag);

        return Inertia::render('back-office/tags/Create', [
            'tag' => $tag,
        ]);
    }

    public function edit(string $slug)
    {
        $tag = $this->tagService->find($slug);
        $tag = $this->tagService->loadRelations($tag);

        Gate::authorize('update', $tag);

        return Inertia::render('back-office/tags/Create', [
            'tag' => $tag,
        ]);
    }

    public function save(TagRequest $request)
    {
        $tag = $this->tagService->new();
        Gate::authorize('create', $tag);

        $result = $this->tagService->save($request, $tag);

        return to_route('back-office.tags.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(TagRequest $request, string $slug)
    {
        $tag = $this->tagService->find($slug);

        Gate::authorize('update', $tag);

        $result = $this->tagService->save($request, $tag);

        return to_route('back-office.tags.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
    {
        $tag = $this->tagService->find($slug);

        Gate::authorize('delete', $tag);

        $result = $this->tagService->delete($tag);

        return to_route('back-office.tags.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
