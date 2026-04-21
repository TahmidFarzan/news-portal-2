<?php

namespace App\Services\BackOffice;

use Spatie\Activitylog\Models\Activity;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ActivityLogService
{
    public function new(): Activity
    {
        return new Activity();
    }

    public function findBySlug(string $slug): Activity
    {
        return Activity::where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(Activity $activity): Activity
    {
        $activity->load([
            'causer',
            'subject',
        ]);

        return $activity;
    }

    public function search(?string $modelSlug, ?string $recordSlug, Request $request)
    {
        $perPage = (int) $request->input('per_page', 10);

        $query = Activity::query();

        if ($modelSlug !== null && $recordSlug !== null) {
            $subjectModel = 'App\\Models\\' . Str::studly($modelSlug);

            $query->where('subject_type', $subjectModel)
                ->whereHas('subject', function ($subjectQuery) use ($recordSlug) {
                    $subjectQuery->where('slug', $recordSlug);
                });
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', $request->input('subject_type'));
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->input('causer_id'));
        }

        if ($request->filled('event')) {
            $query->where('event', $request->input('event'));
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', 'like', '%' . $request->input('log_name') . '%');
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%');
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function delete(Activity $activity): array
    {
        DB::beginTransaction();

        try {

            $activity->delete();

            DB::commit();

            return [
                'status'  => 'success',
                'message' => __('status-messages.activity_log.delete.success'),
            ];
        } catch (Exception $exception) {
            DB::rollBack();

            Log::error('Activity log delete failed.', [
                'exception'    => $exception,
                'activity_log' => $activity,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.activity_log.delete.failed'),
            ];
        }
    }
}
