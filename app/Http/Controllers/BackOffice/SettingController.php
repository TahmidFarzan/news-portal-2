<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingRequest;
use App\Services\BackOffice\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SettingController extends Controller
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
        $this->middleware(['auth', 'verified', 'user.role.check:admin']);
    }

    public function index(Request $request)
    {
        $setting = $this->settingService->new();
        Gate::authorize('viewAny', $setting);

        $settings = $this->settingService->search($request);

        return Inertia::render('back-office/settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function details(string $slug)
    {
        $setting = $this->settingService->find($slug);
        $setting = $this->settingService->loadRelations($setting);

        Gate::authorize('view', $setting);

        return Inertia::render('back-office/settings/Details', [
            'setting' => $setting,
        ]);
    }

    public function edit(string $slug)
    {
        $setting = $this->settingService->find($slug);
        $setting = $this->settingService->loadRelations($setting);

        Gate::authorize('update', $setting);

        return Inertia::render('back-office/settings/Create', [
            'setting' => $setting,
        ]);
    }

    public function update(SettingRequest $request, string $slug)
    {
        $setting = $this->settingService->find($slug);

        Gate::authorize('update', $setting);

        $result = $this->settingService->save($request, $setting);

        return to_route('back-office.settings.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

}
