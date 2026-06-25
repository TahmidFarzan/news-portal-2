<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Services\BackOffice\SettingService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SettingController extends Controller
{
    protected SettingService $settingService;

    public function __construct(SettingService $settingService)
    {
        $this->settingService = $settingService;
    }

    public function index()
    {
        return Inertia::render('back-office/settings/Index');
    }

    public function robotsTxtEdit()
    {
        $robotsTxt = $this->settingService->getRobotsTxt();

        return Inertia::render('back-office/settings/RobotsTxtEdit', [
            'robotsTxt' => $robotsTxt,
        ]);
    }

    public function adsTxtEdit()
    {
        $adsTxt = $this->settingService->getAdsTxt();

        return Inertia::render('back-office/settings/AdsTxtEdit', [
            'adsTxt' => $adsTxt,
        ]);
    }

    public function queueStart()
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueStart();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function queueRestart()
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueRestart();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function queueClear()
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueClear();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function queueFlush()
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueFlush();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function queueMonitorStale()
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueMonitorStale();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function queueMonitorPurge()
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->queueMonitorPurge();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function scheduleStart()
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->scheduleStart();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function scheduleStop()
    {
        if (! app()->environment('production')) {
            return redirect()->route('back-office.settings.index');
        }

        $result = $this->settingService->scheduleStop();

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function robotsTxtSave(Request $request)
    {
        $result = $this->settingService->saveRobotsTxt($request);

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }

    public function adsTxtSave(Request $request)
    {
        $result = $this->settingService->saveAdsTxt($request);

        return to_route('back-office.settings.index')->with('flash_message', $result);
    }
}
