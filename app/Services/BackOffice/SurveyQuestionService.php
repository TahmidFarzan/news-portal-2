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
    public function new(): SurveyQuestion
    {
        return new SurveyQuestion();
    }

    public function find(Survey $survey, string $slug): SurveyQuestion
    {
        return SurveyQuestion::with([
            'survey',
            'surveyQuestionResult',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('survey_id', $survey->id)
            ->where('slug', $slug)
            ->firstOrFail();
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

            DB::transaction(function () use ($request, $survey, $surveyQuestion, $isNew) {
                $surveyQuestion->question  = $request->input('question');
                $surveyQuestion->position  = $request->input('position');
                $surveyQuestion->survey_id = $survey->id;

                $surveyQuestion->created_by_id = $isNew ? Auth::id() : $surveyQuestion->created_by_id;

                $surveyQuestion->save();
            });
            return [
                'status'  => 'success',
                'message' => __("status-messages.survey-question.{$statusEvent}.success"),
                'redirect_back_to_same_page' => (bool) $request->boolean('redirect_back_to_same_page'),
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

    public function reorder(Survey $survey, Request $request): array
    {
        try {
            $questions = $request->input('questions', []);

            DB::transaction(function () use ($survey, $questions) {
                foreach ($questions as $index => $item) {
                    $survey->surveyQuestions()
                        ->where('slug', $item['slug'])
                        ->update([
                            'position' => - ($index + 1),
                        ]);
                }

                foreach ($questions as $item) {
                    $survey
                        ->surveyQuestions()
                        ->where('slug', $item['slug'])
                        ->update([
                            'position' => (int) $item['position'],
                        ]);
                }
            });

            return [
                'status'                     => 'success',
                'message'                    => __('status-messages.survey-question.reorder.success'),
                'redirect_back_to_same_page' => (bool) $request->boolean('redirect_back_to_same_page'),
            ];
        } catch (Exception $exception) {
            Log::error('Failed to reorder survey questions.', [
                'survey_id'          => $survey->id ?? null,
                'survey_question_id' => $surveyQuestion->id ?? null,
                'exception'        => $exception,
            ]);

            return [
                'status'                     => 'error',
                'message'                    => __('status-messages.survey-question.reorder.failed'),
                'redirect_back_to_same_page' => (bool) $request->boolean('redirect_back_to_same_page'),
            ];
        }
    }

    public function saveUsingSurvey(Survey $survey, array $surveyQuestionData): void
    {
        $surveyQuestion = $this->new();

        $surveyQuestion->survey_id       = $survey->id;
        $surveyQuestion->question      = $surveyQuestionData['question'];
        $surveyQuestion->position      = $surveyQuestionData['position'] ?? null;
        $surveyQuestion->created_by_id = Auth::id();
    }
}
