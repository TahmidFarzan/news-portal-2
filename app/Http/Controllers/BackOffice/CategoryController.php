<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Services\BackOffice\CategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CategoryController extends Controller
{
    protected CategoryService $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    public function index(Request $request): InertiaResponse
    {
        $category = $this->categoryService->new();
        Gate::authorize('viewAny', $category);

        $categories = $this->categoryService->search($request);

        return Inertia::render('back-office/categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function details(string $slug): InertiaResponse
    {
        $category = $this->categoryService->find($slug);

        Gate::authorize('view', $category);

        return Inertia::render('back-office/categories/Details', [
            'category' => $category,
        ]);
    }

    public function create(): InertiaResponse
    {
        $category = $this->categoryService->new();
        Gate::authorize('create', $category);

        return Inertia::render('back-office/categories/Create', [
            'category' => $category,
        ]);
    }

    public function edit(string $slug): InertiaResponse
    {
        $category = $this->categoryService->find($slug);

        Gate::authorize('update', $category);

        return Inertia::render('back-office/categories/Create', [
            'category' => $category,
        ]);
    }

    public function save(CategoryRequest $request): RedirectResponse
    {
        $category = $this->categoryService->new();
        Gate::authorize('create', $category);

        $result = $this->categoryService->save($request, $category);

        return to_route('back-office.categories.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(CategoryRequest $request, string $slug): RedirectResponse
    {
        $category = $this->categoryService->find($slug);

        Gate::authorize('update', $category);

        $result = $this->categoryService->save($request, $category);

        return to_route('back-office.categories.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug): RedirectResponse
    {
        $category = $this->categoryService->find($slug);

        Gate::authorize('delete', $category);

        $result = $this->categoryService->delete($category);

        return to_route('back-office.categories.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
