<?php
namespace App\Services\BackOffice;

use App\Http\Requests\TrendRequest;
use App\Models\Trend;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TrendService
{
    public function new (): Trend
    {
        return new Trend();
    }

    public function find(string $slug): Trend
    {
        return Trend::where('slug', $slug)->firstOrFail();
    }

    public function requestedTrend($slug): Trend
    {
        return Trend::where("slug", $slug)->firstOrFail();
    }

    public function loadRelations(Trend $trend): Trend
    {
        $trend->load([
            'tag',
            'tag.language',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $trend;
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Trend::query()->with("tag");

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled("search")) {
            $languageId = $request->input('language_id');
            $query->whereHas('tag', function ($tagQuery) use ($languageId) {
                $tagQuery->where('language_id', $languageId);
            });

        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled("is_current")) {
            $query->where('is_current', true);
        }

        if ($request->filled("date")) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate("created_at", '<=', $date);
        }

        if ($request->filled('search')) {
            $searchValue = $request->input('search');
            $likeSearch  = "%{$searchValue}%";

            $query->whereHas('tag', function ($tagQuery) use ($likeSearch) {
                $tagQuery->whereAny([
                    'name',
                    'brief',
                    'seo_brief',
                    'seo_title',
                ], 'like', $likeSearch);
            });
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(TrendRequest $request, Trend $trend): array
    {
        $isNew       = empty($trend->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $trend, $isNew) {
                $trend->tag_id        = $request->input('tag_id');
                $trend->is_current    = $request->input('is_current') ? true : false;
                $trend->created_by_id = $isNew ? Auth::id() : $trend->created_by_id;
                $trend->save();
            });

            return [
                'status'  => 'success',
                'message' => __("status-messages.trend.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {

            Log::error("Failed to {$statusEvent} trend.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.trend.save.failed'),
            ];
        }
    }

    public function delete(Trend $trend): array
    {

        try {

            DB::transaction(function () use ($trend) {
                $trend->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.trend.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Trend delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.trend.delete.failed'),
            ];
        }
    }

}
