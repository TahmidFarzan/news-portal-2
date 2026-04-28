<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuthorRequest;
use App\Services\BackOffice\AuthorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AuthorController extends Controller
{
    protected AuthorService $authorService;

    public function __construct(AuthorService $authorService)
    {
        $this->authorService = $authorService;
        $this->middleware(['auth', 'verified', 'user.role:admin,supervisor']);
    }

    public function index(Request $request)
    {
        $author = $this->authorService->new();
        Gate::authorize('viewAny', $author);

        $authors = $this->authorService->search($request);

        return Inertia::render('back-office/authors/Index', [
            'authors' => $authors,
        ]);
    }

    public function details(string $slug)
    {
        $author = $this->authorService->find($slug);
        $author = $this->authorService->loadRelations($author);

        Gate::authorize('create', $author);

        return Inertia::render('back-office/authors/Details', [
            'author' => $author,
        ]);
    }

    public function create()
    {
        $author = $this->authorService->new();
        Gate::authorize('create', $author);

        return Inertia::render('back-office/authors/Create', [
            'author' => $author,
        ]);
    }

    public function edit(string $slug)
    {
        $author = $this->authorService->find($slug);
        $author = $this->authorService->loadRelations($author);

        Gate::authorize('update', $author);

        return Inertia::render('back-office/authors/Create', [
            'author' => $author,
        ]);
    }

    public function save(AuthorRequest $request)
    {
        $author = $this->authorService->new();
        Gate::authorize('create', $author);

        $result = $this->authorService->save($request, $author);

        return to_route('back-office.authors.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(AuthorRequest $request, string $slug)
    {
        $author = $this->authorService->find($slug);

        Gate::authorize('update', $author);

        $result = $this->authorService->save($request, $author);

        return to_route('back-office.authors.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
    {
        $author = $this->authorService->find($slug);

        Gate::authorize('delete', $author);

        $result = $this->authorService->delete($author);

        return to_route('back-office.authors.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
