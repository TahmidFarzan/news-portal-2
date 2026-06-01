<?php
namespace App\Services\BackOffice;

use App\Http\Requests\SettingRequest;
use App\Models\Setting;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SettingService
{
    public function new (): Setting
    {
        return new Setting();
    }

    public function find(string $slug): Setting
    {
        return Setting::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Setting $setting): Setting
    {
        $setting->load([
            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $setting;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Setting::query();

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $query->where(function ($q) use ($search) {
                $q->where('label', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%")
                    ->orWhere('key', 'like', '%' . $search . '%')
                    ->orWhere('value', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%');
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(SettingRequest $request, Setting $setting): array
    {
        try {

            DB::transaction(function () use ($request, $setting) {
                $setting->value = $request->input("value");
                $setting->save();
            });
            return [
                'status'  => 'success',
                'message' => __("status-messages.setting.update.success"),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to update setting.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.setting.update.failed'),
            ];
        }
    }
}
