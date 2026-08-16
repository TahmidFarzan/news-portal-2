<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrendRequest;
use App\Services\BackOffice\TrendService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class TrendController extends Controller
{
    protected TrendService $trendService;

    public function __construct(TrendService $trendService)
    {
        $this->trendService = $trendService;
    }

    public function index(Request $request): InertiaResponse
    {
        $trend = $this->trendService->new();
        Gate::authorize('viewAny', $trend);

        $trends = $this->trendService->search($request);

        return Inertia::render('back-office/trends/Index', [
            'trends' => $trends,
        ]);
    }

    public function details(string $slug): InertiaResponse
    {
        $trend = $this->trendService->find($slug);

        Gate::authorize('view', $trend);

        return Inertia::render('back-office/trends/Details', [
            'trend' => $trend,
        ]);
    }

    public function create(): InertiaResponse
    {
        $trend = $this->trendService->new();
        Gate::authorize('create', $trend);

        return Inertia::render('back-office/trends/Create', [
            'trend' => $trend,
        ]);
    }

    public function edit(string $slug): InertiaResponse
    {
        $trend = $this->trendService->find($slug);

        Gate::authorize('update', $trend);

        return Inertia::render('back-office/trends/Create', [
            'trend' => $trend,
        ]);
    }

    public function save(TrendRequest $request): RedirectResponse
    {
        $trend = $this->trendService->new();
        Gate::authorize('create', $trend);

        $result = $this->trendService->save($request, $trend);

        return to_route('back-office.trends.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(TrendRequest $request, string $slug): RedirectResponse
    {
        $trend = $this->trendService->find($slug);

        Gate::authorize('update', $trend);

        $result = $this->trendService->save($request, $trend);

        return to_route('back-office.trends.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug): RedirectResponse
    {
        $trend = $this->trendService->find($slug);

        Gate::authorize('delete', $trend);

        $result = $this->trendService->delete($trend);

        return to_route('back-office.trends.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
