<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoogleAdsenseRequest;
use App\Services\BackOffice\GoogleAdsenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;


class GoogleAdsenseController extends Controller
{
    protected GoogleAdsenseService $googleAdsenseService;

    public function __construct(GoogleAdsenseService $googleAdsenseService)
    {
        $this->googleAdsenseService = $googleAdsenseService;
    }

    public function index(Request $request)
    {
        $googleAdsense = $this->googleAdsenseService->new();
        Gate::authorize('viewAny', $googleAdsense);

        $googleAdsenses = $this->googleAdsenseService->search($request);

        return Inertia::render('back-office/google-adsenses/Index', [
            'googleAdsenses' => $googleAdsenses,
        ]);
    }

    public function details(string $slug)
    {
        $googleAdsense = $this->googleAdsenseService->find($slug);

        Gate::authorize('view', $googleAdsense);

        return Inertia::render('back-office/google-adsenses/Details', [
            'googleAdsense' => $googleAdsense,
        ]);
    }

    public function create()
    {
        $googleAdsense = $this->googleAdsenseService->new();
        Gate::authorize('create', $googleAdsense);

        return Inertia::render('back-office/google-adsenses/Create', [
            'googleAdsense' => $googleAdsense,
        ]);
    }

    public function edit(string $slug)
    {
        $googleAdsense = $this->googleAdsenseService->find($slug);

        Gate::authorize('update', $googleAdsense);

        return Inertia::render('back-office/google-adsenses/Create', [
            'googleAdsense' => $googleAdsense,
        ]);
    }

    public function save(GoogleAdsenseRequest $request)
    {
        $googleAdsense = $this->googleAdsenseService->new();
        Gate::authorize('create', $googleAdsense);

        $result = $this->googleAdsenseService->save($request, $googleAdsense);

        return to_route('back-office.google-adsenses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(GoogleAdsenseRequest $request, string $slug)
    {
        $googleAdsense = $this->googleAdsenseService->find($slug);

        Gate::authorize('update', $googleAdsense);

        $result = $this->googleAdsenseService->save($request, $googleAdsense);

        return to_route('back-office.google-adsenses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
    {
        $googleAdsense = $this->googleAdsenseService->find($slug);

        Gate::authorize('delete', $googleAdsense);

        $result = $this->googleAdsenseService->delete($googleAdsense);

        return to_route('back-office.google-adsenses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
