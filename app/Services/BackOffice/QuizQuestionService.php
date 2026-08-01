<?php

namespace App\Services\BackOffice;

use App\Http\Requests\QuizQuestionRequest;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Services\BackOffice\QuizQuestionOptionService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizQuestionService
{
    protected QuizQuestionOptionService $quizQuestionOptionService;

    public function __construct(QuizQuestionOptionService $quizQuestionOptionService)
    {
        $this->quizQuestionOptionService = $quizQuestionOptionService;
    }

    public function new(): QuizQuestion
    {
        return new QuizQuestion();
    }

    public function find(Quiz $quiz, string $slug): QuizQuestion
    {
        return QuizQuestion::with([
            "quiz",
            'quizQuestionOptions' => fn($query) => $query->latest()->limit(10),

            'createdBy',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('quiz_id', $quiz->id)->where('slug', $slug)->firstOrFail();
    }

    public function search(Quiz $quiz, Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = QuizQuestion::query()->where('quiz_id', $quiz->id);

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('answer_type')) {
            $query->where('answer_type', $request->input('answer_type'));
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
                'point',
                'position',
            ], 'like', $likeSearch);
        }

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function save(Quiz $quiz, QuizQuestionRequest $request, QuizQuestion $quizQuestion): array
    {
        $isNew       = empty($quizQuestion->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $quiz, $quizQuestion, $isNew) {
                $quizQuestion->quiz_id     = $quiz->id;
                $quizQuestion->question    = $request->input("question");
                $quizQuestion->point       = $request->input("point", 1);
                $quizQuestion->position    = $request->input("position");
                $quizQuestion->answer_type = $request->input('answer_type');

                $quizQuestion->created_by_id = $isNew ? Auth::id() : $quizQuestion->created_by_id;

                $save = $quizQuestion->save();

                if ($save && $isNew) {
                    if ($request->filled('options')) {
                        foreach ($request->input('options', []) as $quizQuestionOptionData) {
                            $this->quizQuestionOptionService->saveUsingQuizQuestion($quizQuestion, $quizQuestionOptionData);
                        }
                    }
                }
            });
            return [
                'status'  => 'success',
                'message' => __("status-messages.quiz-question.{$statusEvent}.success"),
                'redirect_back_to_same_page' => (bool) $request->boolean('redirect_back_to_same_page'),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to {$statusEvent} quiz question.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.quiz-question.save.failed'),
            ];
        }
    }

    public function saveUsingQuiz(Quiz $quiz, array $quizQuestionData): void
    {
        $quizQuestion = $this->new();

        $quizQuestion->quiz_id       = $quiz->id;
        $quizQuestion->question      = $quizQuestionData['question'];
        $quizQuestion->answer_type   = $quizQuestionData['answer_type'];
        $quizQuestion->point         = $quizQuestionData['point'];
        $quizQuestion->position      = $quizQuestionData['position'] ?? null;
        $quizQuestion->created_by_id = Auth::id();

        $quizQuestion->save();

        foreach ($quizQuestionData['quiz_question_options'] as $quizQuestionOptionData) {
            $this->quizQuestionOptionService->saveUsingQuizQuestion($quizQuestion, $quizQuestionOptionData);
        }
    }

    public function delete(Quiz $quiz, QuizQuestion $quizQuestion): array
    {

        try {

            DB::transaction(function () use ($quizQuestion) {
                $quizQuestion->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.quiz-question.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Quiz delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.quiz-question.delete.failed'),
            ];
        }
    }

    public function reorder(Quiz $quiz, Request $request): array
    {
        try {
            $questions = $request->input('questions', []);

            DB::transaction(function () use ($quiz,$questions) {
                foreach ($questions as $index => $item) {
                    $quiz->quizQuestions()
                        ->where('slug', $item['slug'])
                        ->update([
                            'position' => - ($index + 1),
                        ]);
                }

                foreach ($questions as $item) {
                    $quiz
                        ->quizQuestions()
                        ->where('slug', $item['slug'])
                        ->update([
                            'position' => (int) $item['position'],
                        ]);
                }
            });

            return [
                'status'                     => 'success',
                'message'                    => __('status-messages.quiz-question.reorder.success'),
                'redirect_back_to_same_page' => (bool) $request->boolean('redirect_back_to_same_page'),
            ];
        } catch (Exception $exception) {
            Log::error('Failed to reorder quiz questions.', [
                'quiz_id'          => $quiz->id ?? null,
                'quiz_question_id' => $quizQuestion->id ?? null,
                'exception'        => $exception,
            ]);

            return [
                'status'                     => 'error',
                'message'                    => __('status-messages.quiz-question.reorder.failed'),
                'redirect_back_to_same_page' => (bool) $request->boolean('redirect_back_to_same_page'),
            ];
        }
    }
}
