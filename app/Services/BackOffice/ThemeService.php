<?php
namespace App\Services\BackOffice;

use App\Http\Requests\ThemeRequest;
use App\Models\Theme;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ThemeService
{
    public function new (): Theme
    {
        return new Theme();
    }

    public function find(string $slug): Theme
    {
        return Theme::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Theme $theme): Theme
    {
        $theme->load([
            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $theme;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Theme::query();

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('search')) {
            $search     = $request->input('search');
            $likeSearch = "%{$search}%";

            $query->whereAny([
                'label',
                'group',
                'key',
                'value',
                'type',
            ], 'like', $likeSearch);
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(ThemeRequest $request, Theme $theme): array
    {
        try {

            DB::transaction(function () use ($request, $theme) {
                $theme->value = $request->input("value");
                $theme->save();
            });
            return [
                'status'  => 'success',
                'message' => __("status-messages.theme.update.success"),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to update theme.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.theme.update.failed'),
            ];
        }
    }
}
