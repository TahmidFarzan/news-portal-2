<?php

namespace App\Services\BackOffice;

use App\Models\Quiz;
use App\Models\QuizResult;
use Exception;
use Illuminate\Http\Request;

class QuizResultService
{
    public function find(Quiz $quiz, string $slug): QuizResult
    {
        return QuizResult::with([
            'quiz',
            'quizParticipant',

            'activityLogs' => fn($query) => $query->latest()->limit(10),
            'activityLogs.causer',

            'latestActivityLog',
            'latestActivityLog.causer',
        ])->where('slug', $slug)->firstOrFail();
    }

    public function search(Quiz $quiz, Request $request)
    {
        $perPage = $request->input('per_page', 10);

        $query = QuizResult::query()->with(["quiz", "quizParticipant"]);

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $query->whereDate('created_at', '<=', $date);
        }

        $query->where('quiz_id', $quiz->id);

        return $query->orderByDesc('id')
            ->paginate($perPage)
            ->appends($request->all());
    }

    public function syncQuizResultPointByQuiz(Quiz $quiz, float $point = 0, bool $isIncrease = true): void
    {
        $query = QuizResult::where('quiz_id', $quiz->id);

        if ($isIncrease) {
            $query->increment('total_point', $point);
        } else {
            $query->decrement('total_point', $point);
        }
    }
}
