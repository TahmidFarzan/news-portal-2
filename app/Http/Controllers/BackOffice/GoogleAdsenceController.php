<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleAdsenceRequest;
use App\Services\BackOffice\GoogleAdsenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;


class GoogleAdsenceController extends Controller
{
    protected GoogleAdsenceService $googleAdsenceService;

    public function __construct(GoogleAdsenceService $googleAdsenceService)
    {
        $this->googleAdsenceService = $googleAdsenceService;
    }

    public function index(Request $request)
    {
        $googleAdsence = $this->googleAdsenceService->new();
        Gate::authorize('viewAny', $googleAdsence);

        $googleAdsences = $this->googleAdsenceService->search($request);

        return Inertia::render('back-office/google-adsences/Index', [
            'googleAdsences' => $googleAdsences,
        ]);
    }

    public function details(string $slug)
    {
        $googleAdsence = $this->googleAdsenceService->find($slug);

        Gate::authorize('view', $googleAdsence);

        return Inertia::render('back-office/google-adsences/Details', [
            'googleAdsence' => $googleAdsence,
        ]);
    }

    public function create()
    {
        $googleAdsence = $this->googleAdsenceService->new();
        Gate::authorize('create', $googleAdsence);

        return Inertia::render('back-office/google-adsences/Create', [
            'googleAdsence' => $googleAdsence,
        ]);
    }

    public function edit(string $slug)
    {
        $googleAdsence = $this->googleAdsenceService->find($slug);

        Gate::authorize('update', $googleAdsence);

        return Inertia::render('back-office/google-adsences/Create', [
            'googleAdsence' => $googleAdsence,
        ]);
    }

    public function save(GoogleAdsenceRequest $request)
    {
        $googleAdsence = $this->googleAdsenceService->new();
        Gate::authorize('create', $googleAdsence);

        $result = $this->googleAdsenceService->save($request, $googleAdsence);

        return to_route('back-office.google-adsences.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(GoogleAdsenceRequest $request, string $slug)
    {
        $googleAdsence = $this->googleAdsenceService->find($slug);

        Gate::authorize('update', $googleAdsence);

        $result = $this->googleAdsenceService->save($request, $googleAdsence);

        return to_route('back-office.google-adsences.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
    {
        $googleAdsence = $this->googleAdsenceService->find($slug);

        Gate::authorize('delete', $googleAdsence);

        $result = $this->googleAdsenceService->delete($googleAdsence);

        return to_route('back-office.google-adsences.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
