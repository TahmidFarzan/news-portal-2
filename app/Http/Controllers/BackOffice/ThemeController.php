<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ThemeRequest;
use App\Services\BackOffice\ThemeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ThemeController extends Controller
{
    protected ThemeService $themeService;

    public function __construct(ThemeService $themeService)
    {
        $this->themeService = $themeService;
    }

    public function index(Request $request)
    {
        $theme = $this->themeService->new();
        Gate::authorize('viewAny', $theme);

        $themes = $this->themeService->search($request);

        return Inertia::render('back-office/themes/Index', [
            'themes' => $themes,
        ]);
    }

    public function details(string $slug)
    {
        $theme = $this->themeService->find($slug);
        $theme = $this->themeService->loadRelations($theme);

        Gate::authorize('view', $theme);

        return Inertia::render('back-office/themes/Details', [
            'theme' => $theme,
        ]);
    }

    public function edit(string $slug)
    {
        $theme = $this->themeService->find($slug);
        $theme = $this->themeService->loadRelations($theme);

        Gate::authorize('update', $theme);

        return Inertia::render('back-office/themes/Create', [
            'theme' => $theme,
        ]);
    }

    public function update(ThemeRequest $request, string $slug)
    {
        $theme = $this->themeService->find($slug);

        Gate::authorize('update', $theme);

        $result = $this->themeService->save($request, $theme);

        return to_route('back-office.themes.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

}
