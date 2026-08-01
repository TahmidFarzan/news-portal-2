<?php
namespace App\Services\BackOffice;

use App\Http\Requests\QuizQuestionOptionRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizQuestionOptionService
{
    public function new (): QuizQuestionOption
    {
        return new QuizQuestionOption();
    }

    public function find(QuizQuestion $quizQuestion, string $slug): QuizQuestionOption
    {
        return QuizQuestionOption::with([
            'quizQuestion',
            'quizQuestion.quiz',

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('quiz_question_id', $quizQuestion->id)->where('slug', $slug)->firstOrFail();
    }

    public function search(QuizQuestion $quizQuestion, Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = QuizQuestionOption::query()->where("quiz_question_id",$quizQuestion->id);

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('is_correct')) {
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
                'position',
            ], 'like', $likeSearch);
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(QuizQuestion $quizQuestion, QuizQuestionOptionRequest $request, QuizQuestionOption $quizQuestionOption): array
    {
        $isNew       = empty($quizQuestionOption->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $quizQuestion, $quizQuestionOption, $isNew) {
                $quizQuestionOption->quiz_question_id = $quizQuestion->id;
                $quizQuestionOption->option           = $request->input("option");
                $quizQuestionOption->is_correct       = $request->boolean("is_correct", false) ? true : false;
                $quizQuestionOption->position         = $request->input("position", $quizQuestionOption->position);

                $quizQuestionOption->created_by_id = $isNew ? Auth::id() : $quizQuestionOption->created_by_id;

                $quizQuestionOption->save();
            });
            return [
                "redirect_back_to_same_page" => $request->boolean('redirect_back_to_same_page', false) ? true: false,
                'status'  => 'success',
                'message' => __("status-messages.quiz-question-option.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to {$statusEvent} quiz question option.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.quiz-question-option.save.failed'),
            ];
        }
    }

    public function saveUsingQuizQuestion(QuizQuestion $quizQuestion, array $quizQuestionOptionData): void
    {
        $quizQuestionOption = $this->new();

        $quizQuestionOption->quiz_question_id = $quizQuestion->id;
        $quizQuestionOption->option           = $quizQuestionOptionData['option'];
        $quizQuestionOption->is_correct       = $quizQuestionOptionData['is_correct'];
        $quizQuestionOption->position         = $quizQuestionOptionData['position'] ?? null;
        $quizQuestionOption->created_by_id    = Auth::id();

        $quizQuestionOption->save();
    }

    public function delete(QuizQuestion $quizQuestion, QuizQuestionOption $quizQuestionOption): array
    {

        try {

            DB::transaction(function () use ($quizQuestionOption) {
                $quizQuestionOption->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.quiz-question-option.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Quiz delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.quiz-question-option.delete.failed'),
            ];
        }
    }

}
