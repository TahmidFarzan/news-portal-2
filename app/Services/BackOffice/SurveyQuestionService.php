<?php
namespace App\Services\BackOffice;

use App\Http\Requests\SurveyQuestionRequest;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SurveyQuestionService
{
    public function new (): SurveyQuestion
    {
        return new SurveyQuestion();
    }

    public function find(Survey $survey, string $slug): SurveyQuestion
    {
        return SurveyQuestion::where('survey_id', $survey->id)->where('slug', $slug)->firstOrFail();
    }

    public function loadRelations(SurveyQuestion $surveyQuestion): SurveyQuestion
    {
        $surveyQuestion->load([
            'survey',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ]);

        return $surveyQuestion;
    }

    public function search(Request $request, Survey $survey)
    {
        $perPage = $request->input('per_page', 10);

        $query = SurveyQuestion::query()->where("survey_id", $survey->id);

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('search')) {
            $search     = $request->input('search');
            $likeSearch = "%{$search}%";

            $query->whereAny([
                'question',
            ], 'like', $likeSearch);
        }
        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(SurveyQuestionRequest $request, Survey $survey, SurveyQuestion $surveyQuestion): array
    {
        $isNew       = empty($surveyQuestion->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request,$survey, $surveyQuestion, $isNew) {
                $surveyQuestion->question    = $request->input('question');
                $surveyQuestion->survey_id   = $survey->id;

                $surveyQuestion->created_by_id = $isNew ? Auth::id() : $surveyQuestion->created_by_id;

                $surveyQuestion->save();
            });
            return [
                'status'  => 'success',
                'message' => __("status-messages.survey-question.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to {$statusEvent} survey.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.survey-question.save.failed'),
            ];
        }
    }

    public function delete(SurveyQuestion $surveyQuestion): array
    {

        try {
            DB::transaction(function () use ($surveyQuestion) {
                $surveyQuestion->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.survey-question.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Survey delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.survey-question.delete.failed'),
            ];
        }
    }
}
