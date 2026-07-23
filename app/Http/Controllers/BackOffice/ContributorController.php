<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContributorRequest;
use App\Services\BackOffice\ContributorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class ContributorController extends Controller
{
    protected ContributorService $contributorService;

    public function __construct(ContributorService $contributorService)
    {
        $this->contributorService = $contributorService;
    }

    public function index(Request $request)
    {
        $contributor = $this->contributorService->new();
        Gate::authorize('viewAny', $contributor);

        $contributors = $this->contributorService->search($request);

        return Inertia::render('back-office/contributors/Index', [
            'contributors' => $contributors,
        ]);
    }

    public function details(string $slug)
    {
        $contributor = $this->contributorService->find($slug);

        Gate::authorize('view', $contributor);

        return Inertia::render('back-office/contributors/Details', [
            'contributor' => $contributor,
        ]);
    }

    public function create()
    {
        $contributor = $this->contributorService->new();
        Gate::authorize('create', $contributor);

        return Inertia::render('back-office/contributors/Create', [
            'contributor' => $contributor,
        ]);
    }

    public function edit(string $slug)
    {
        $contributor = $this->contributorService->find($slug);

        Gate::authorize('update', $contributor);

        return Inertia::render('back-office/contributors/Create', [
            'contributor' => $contributor,
        ]);
    }

    public function save(ContributorRequest $request)
    {
        $contributor = $this->contributorService->new();
        Gate::authorize('create', $contributor);

        $result = $this->contributorService->save($request, $contributor);

        return to_route('back-office.contributors.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(ContributorRequest $request, string $slug)
    {
        $contributor = $this->contributorService->find($slug);

        Gate::authorize('update', $contributor);

        $result = $this->contributorService->save($request, $contributor);

        return to_route('back-office.contributors.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
    {
        $contributor = $this->contributorService->find($slug);

        Gate::authorize('delete', $contributor);

        $result = $this->contributorService->delete($contributor);

        return to_route('back-office.contributors.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
