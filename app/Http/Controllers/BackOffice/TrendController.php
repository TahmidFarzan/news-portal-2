<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\TrendRequest;
use App\Services\BackOffice\TrendService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TrendController extends Controller
{
    protected TrendService $trendService;

    public function __construct(TrendService $trendService)
    {
        $this->trendService = $trendService;
        $this->middleware(['auth', 'verified', 'user.role:admin,news_desk']);
    }

    public function index(Request $request)
    {
        $trend = $this->trendService->new();
        Gate::authorize('viewAny', $trend);

        $trends = $this->trendService->search($request);

        return Inertia::render('back-office/trends/Index', [
            'trends' => $trends,
        ]);
    }

    public function details(string $slug)
    {
        $trend = $this->trendService->find($slug);
        $trend = $this->trendService->loadRelations($trend);

        Gate::authorize('create', $trend);

        return Inertia::render('back-office/trends/Details', [
            'trend' => $trend,
        ]);
    }

    public function create()
    {
        $trend = $this->trendService->new();
        Gate::authorize('create', $trend);

        return Inertia::render('back-office/trends/Create', [
            'trend' => $trend,
        ]);
    }

    public function edit(string $slug)
    {
        $trend = $this->trendService->find($slug);
        $trend = $this->trendService->loadRelations($trend);

        Gate::authorize('update', $trend);

        return Inertia::render('back-office/trends/Create', [
            'trend' => $trend,
        ]);
    }

    public function save(TrendRequest $request)
    {
        $trend = $this->trendService->new();
        Gate::authorize('create', $trend);

        $result = $this->trendService->save($request, $trend);

        return to_route('back-office.trends.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(TrendRequest $request, string $slug)
    {
        $trend = $this->trendService->find($slug);

        Gate::authorize('update', $trend);

        $result = $this->trendService->save($request, $trend);

        return to_route('back-office.trends.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
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
