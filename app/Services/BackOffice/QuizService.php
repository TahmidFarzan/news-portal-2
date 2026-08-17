<?php

namespace App\Services\BackOffice;

use App\Http\Requests\QuizRequest;
use App\Models\Quiz;
use App\Services\BackOffice\QuizQuestionService;
use App\Services\BackOffice\QuizResultService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizService
{
    protected QuizQuestionService $quizQuestionService;

    public function __construct(QuizQuestionService $quizQuestionService, QuizResultService $quizResultService)
    {
        $this->quizQuestionService = $quizQuestionService;
    }

    public function new(): Quiz
    {
        return new Quiz();
    }

    public function find(string $slug): Quiz
    {
        return Quiz::with([
            'quizQuestions' => fn($query) => $query->latest()->limit(10),
            'quizQuestions.quiz' => fn($query) => $query->latest()->limit(10),
            'quizQuestions.quizQuestionOptions' => fn($query) => $query->latest()->limit(10),

            'quizResults' => fn($query) => $query->latest()->limit(10),
            'quizResults.quizParticipant',

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

        $query = Quiz::query()->with(["language"]);

        if ($request->filled('created_by_id')) {
            $query->where('created_by_id', $request->input('created_by_id'));
        }

        if ($request->filled('language_id')) {
            $query->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }
        if ($request->filled('show_bellow_event')) {
            $query->where('show_bellow_event', $request->boolean('show_bellow_event'));
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date);
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

    public function save(QuizRequest $request, Quiz $quiz): array
    {
        $isNew       = empty($quiz->id);
        $statusEvent = $isNew ? "save" : "update";

        try {

            DB::transaction(function () use ($request, $quiz, $isNew) {

                $quiz->name              = $request->input('name');
                $quiz->brief             = $request->input('brief');
                $quiz->language_id       = $request->input('language_id');
                $quiz->start_date        = $request->input('start_date');
                $quiz->end_date          = $request->input('end_date', $request->input('start_date'));
                $quiz->is_active         = $request->boolean('is_active', false) ? true : false;
                $quiz->show_bellow_event = $request->boolean('show_bellow_event', false) ? true : false;
                $quiz->enable_result = $request->boolean('enable_result', false) ? true : false;
                $quiz->max_winner             = $request->input('max_winner', 1);
                $quiz->created_by_id     = $isNew ? Auth::id() : $quiz->created_by_id;

                $save = $quiz->save();

                if ($save && $isNew) {
                    if ($request->filled('questions')) {
                        $this->quizQuestionService->saveUsingQuiz($quiz, $request->input('questions', []));
                    }
                }
            });
            return [
                'status'  => 'success',
                'message' => __("status-messages.quiz.{$statusEvent}.success"),
            ];
        } catch (Exception $exception) {
            Log::error("Failed to {$statusEvent} quiz.", [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.quiz.save.failed'),
            ];
        }
    }

    public function active(Quiz $quiz): array
    {

        try {
            DB::transaction(function () use ($quiz) {
                $quiz->is_active = true;
                $quiz->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.quiz.active.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Quiz active failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.quiz.active.failed'),
            ];
        }
    }

    public function inactive(Quiz $quiz): array
    {

        try {
            DB::transaction(function () use ($quiz) {
                $quiz->is_active = false;
                $quiz->save();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.quiz.inactive.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Quiz inactive failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.quiz.inactive.failed'),
            ];
        }
    }

    public function delete(Quiz $quiz): array
    {

        try {

            DB::transaction(function () use ($quiz) {
                $quiz->delete();
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.quiz.delete.success'),
            ];
        } catch (Exception $exception) {

            Log::error('Quiz delete failed.', [
                'exception' => $exception,
            ]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.quiz.delete.failed'),
            ];
        }
    }

    public function quizWinnerResults(Quiz $quiz)
    {
        return $quiz->quizResults()->with(["quiz","quizParticipant"])
            ->orderByDesc('total_point')
            ->orderBy('duration')
            ->take($quiz->max_winner ?? 1)
            ->get();
    }
}
