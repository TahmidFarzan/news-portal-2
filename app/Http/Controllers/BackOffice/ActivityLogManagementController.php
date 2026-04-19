<?php

namespace App\Http\Controllers\BackOffice;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use App\Services\BackOffice\ActivityLogManagementService;
use Inertia\Inertia;

class ActivityLogManagementController extends Controller
{
    protected ActivityLogManagementService $service;

    public function __construct(ActivityLogManagementService $service)
    {
        $this->service = $service;
        $this->middleware(['auth', 'verified', 'user.role:admin']);
    }

    public function index(Request $request)
    {
        $showSubjectType = true;
        $activityLog = $this->service->new();

        Gate::authorize('viewAny', $activityLog);

        $activityLogs = $this->service->search(null, null, $request);

        return Inertia::render('back-office/activity-logs/Index', [
            'activityLogs' => $activityLogs,
            'showSubjectType' => $showSubjectType,
        ]);
    }

    public function indexForModel(?string $modelSlug = null, ?string $recordSlug = null, Request $request)
    {
        $showSubjectType = false;
        $activityLog = $this->service->new();

        Gate::authorize('viewAny', $activityLog);

        $activityLogs = $this->service->search($modelSlug, $recordSlug, $request);

        return Inertia::render('back-office/activity-logs/Index', [
            'activityLogs' => $activityLogs,
            'showSubjectType' => $showSubjectType,
        ]);
    }

    public function details(string $slug)
    {
        $activityLog = $this->service->findBySlug($slug);
        $activityLog = $this->service->loadRelations($activityLog);

        Gate::authorize('view', $activityLog);

        return Inertia::render('back-office/activity-logs/Details', [
            'activityLog' => $activityLog,
        ]);
    }

    public function delete(string $slug)
    {
        $activityLog = $this->service->findBySlug($slug);

        Gate::authorize('delete', $activityLog);

        $result = $this->service->delete($activityLog);

        return to_route('back-office.activity-logs.index')->with('flash_message', [
            'message' => $result['message'],
            'status' => $result['status'],
        ]);
    }
}
