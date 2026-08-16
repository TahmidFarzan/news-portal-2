<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\LocationRequest;
use App\Services\BackOffice\LocationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class LocationController extends Controller
{
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->locationService = $locationService;
    }

    public function index(Request $request): InertiaResponse
    {
        $location = $this->locationService->new();
        Gate::authorize('viewAny', $location);

        $locations = $this->locationService->search($request);

        return Inertia::render('back-office/locations/Index', [
            'locations' => $locations,
        ]);
    }

    public function details(string $slug): InertiaResponse
    {
        $location = $this->locationService->find($slug);

        Gate::authorize('view', $location);

        return Inertia::render('back-office/locations/Details', [
            'location' => $location,
        ]);
    }

    public function create(): InertiaResponse
    {
        $location = $this->locationService->new();
        Gate::authorize('create', $location);

        return Inertia::render('back-office/locations/Create', [
            'location' => $location,
        ]);
    }

    public function edit(string $slug): InertiaResponse
    {
        $location = $this->locationService->find($slug);

        Gate::authorize('update', $location);

        return Inertia::render('back-office/locations/Create', [
            'location' => $location,
        ]);
    }

    public function save(LocationRequest $request): RedirectResponse
    {
        $location = $this->locationService->new();
        Gate::authorize('create', $location);

        $result = $this->locationService->save($request, $location);

        return to_route('back-office.locations.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(LocationRequest $request, string $slug): RedirectResponse
    {
        $location = $this->locationService->find($slug);

        Gate::authorize('update', $location);

        $result = $this->locationService->save($request, $location);

        return to_route('back-office.locations.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug): RedirectResponse
    {
        $location = $this->locationService->find($slug);

        Gate::authorize('delete', $location);

        $result = $this->locationService->delete($location);

        return to_route('back-office.locations.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
