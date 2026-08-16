<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleAdRequest;
use App\Services\BackOffice\GoogleAdService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;


class GoogleAdController extends Controller
{
    protected GoogleAdService $googleAdService;

    public function __construct(GoogleAdService $googleAdService)
    {
        $this->googleAdService = $googleAdService;
    }

    public function index(Request $request): InertiaResponse
    {
        $googleAd = $this->googleAdService->new();
        Gate::authorize('viewAny', $googleAd);

        $googleAds = $this->googleAdService->search($request);

        return Inertia::render('back-office/google-ads/Index', [
            'googleAds' => $googleAds,
        ]);
    }

    public function details(string $slug): InertiaResponse
    {
        $googleAd = $this->googleAdService->find($slug);

        Gate::authorize('view', $googleAd);

        return Inertia::render('back-office/google-ads/Details', [
            'googleAd' => $googleAd,
        ]);
    }

    public function create(): InertiaResponse
    {
        $googleAd = $this->googleAdService->new();
        Gate::authorize('create', $googleAd);

        return Inertia::render('back-office/google-ads/Create', [
            'googleAd' => $googleAd,
        ]);
    }

    public function edit(string $slug): InertiaResponse
    {
        $googleAd = $this->googleAdService->find($slug);

        Gate::authorize('update', $googleAd);

        return Inertia::render('back-office/google-ads/Create', [
            'googleAd' => $googleAd,
        ]);
    }

    public function save(GoogleAdRequest $request): RedirectResponse
    {
        $googleAd = $this->googleAdService->new();
        Gate::authorize('create', $googleAd);

        $result = $this->googleAdService->save($request, $googleAd);

        return to_route('back-office.google-ads.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(GoogleAdRequest $request, string $slug): RedirectResponse
    {
        $googleAd = $this->googleAdService->find($slug);

        Gate::authorize('update', $googleAd);

        $result = $this->googleAdService->save($request, $googleAd);

        return to_route('back-office.google-ads.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug): RedirectResponse
    {
        $googleAd = $this->googleAdService->find($slug);

        Gate::authorize('delete', $googleAd);

        $result = $this->googleAdService->delete($googleAd);

        return to_route('back-office.google-ads.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
