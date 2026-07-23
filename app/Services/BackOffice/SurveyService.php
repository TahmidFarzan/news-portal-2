<?php
namespace App\Services\BackOffice;

use App\Http\Requests\SurveyRequest;
use App\Models\Survey;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurveyService
{
    public function new (): Survey
    {
        return new Survey();
    }

    public function find(string $slug): Survey
    {
        return Survey::with([
            'surveyQuestions',
            'language',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('slug', $slug)->firstOrFail();
    }

    public function search(Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = Survey::query()->with(["language"]);

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('date', '<=', $date)
                ->orWhereDate('created_at', '<=', $date);
        }

        if ($request->filled('search')) {
            $search     = $request->input('search');
            $likeSearch = "%{$search}%";

            $query->whereAny([
                'name',
                'brief',
            ], 'like', $likeSearch);
        }
        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(SurveyRequest $request, Survey $survey): array
    {
        $isNew       = empty($survey->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $survey, $isNew) {
                $survey->name        = $request->input('name');
                $survey->brief       = $request->input('brief');
                $survey->date        = $request->input('date', now());
                $survey->language_id = $request->input('language_id');

                $survey->is_active = $request->input('is_active') ? true : false;

                $survey->created_by_id = $isNew ? Auth::id() : $survey->created_by_id;

                $survey->save();
            });
            return [
                'status'  => 'success',
                'message' => __("status-messages.survey.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to {$statusEvent} survey.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.survey.save.failed'),
            ];
        }
    }

    public function active(Survey $survey): array
    {

        try {
            DB::transaction(function () use ($survey) {
                $survey->is_active = true;
                $survey->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.survey.active.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Survey active failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.survey.active.failed'),
            ];
        }
    }

    public function inactive(Survey $survey): array
    {

        try {
            DB::transaction(function () use ($survey) {
                $survey->is_active = false;
                $survey->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.survey.inactive.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Survey inactive failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.survey.inactive.failed'),
            ];
        }
    }

    public function delete(Survey $survey): array
    {

        try {
            DB::transaction(function () use ($survey) {
                $survey->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.survey.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Survey delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.survey.delete.failed'),
            ];
        }
    }
}
