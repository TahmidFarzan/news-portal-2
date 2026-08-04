<?php

namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Language;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizParticipant;
use App\Models\QuizResult;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class QuizCacheService
{
    private int $cachedTTL = 300;

    private string $mainTag = CacheHelper::TAG_QUIZ;

    private string $secondKey = CacheHelper::KEY_QUIZ;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_PAGE]);
    }

    private function dbQuizBySlug(Language $language, string $slug): Quiz
    {
        $record = Quiz::query()->with([
            'quizQuestions',
            'quizQuestions.quizQuestionOptions',
        ])
            ->where('is_active', true);

        if ($language && $language?->id) {
            $record = $record->where('language_id', $language?->id);
        }

        $record = $record->where('slug', $slug)->firstOrFail();

        return $record;
    }

    private function dbQuizzesByDate(Language $language, string $nowDate, Request|null $request = null): Collection
    {
        $showBellowEvent = false;
        $request ??= request();

        $records = Quiz::query()->with([
            'language',
        ])
            ->whereDate('start_date', '<=', $nowDate)
            ->whereDate('end_date', '>=', $nowDate)
            ->where('is_active', true);

        if ($language && $language?->id) {
            $records = $records->where('language_id', $language?->id);
        }

        if ($request->filled("show_bellow_event")) {
            $showBellowEvent = $request->input("show_bellow_event", false);
        }

        $records->where('show_bellow_event', $showBellowEvent);

        $records = $records->get();

        return $records;
    }

    private function dbQuizQuestionByQuiz(Language $language, Quiz $quiz, Request | null $request = null, string|int $perPage = 10): LengthAwarePaginator
    {
        $records = QuizQuestion::query()->with([
            'quizQuestions',
            'quiz.language',
            'quizQuestionOptions',
        ]);

        $records = $records->where('quiz_id', $quiz->id);

        $records = $records->whereRelation('quiz', 'language_id', $language->id);

        $records = $records->where('quiz_id', $quiz->id)->paginate($perPage);

        return $records;
    }

    private function dbQuizWinnerResultsByQuiz(Quiz $quiz)
    {
        return QuizResult::with(["quizParticipant"])
            ->where('quiz_id', $quiz->id)
            ->orderByDesc('total_point')
            ->orderBy('duration')
            ->take($quiz->max_winner ?? 1)->get();
    }

    private function dbPreviousQuiz(Language $language, string $nowDate): ?Quiz
    {
        return Quiz::query()
            ->with([
                'language',
            ])
            ->where('language_id', $language->id)
            ->whereDate('end_date', '<', $nowDate)
            ->where('is_active', true)
            ->orderBy('end_date',"desc")
            ->orderBy('id',"desc")
            ->first() ?? null;
    }

    public function getQuizBySlug(string $key, Language $language, string $slug, ?int $cachedTTL = null): Quiz
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordBySlug($key, $this->secondKey, $slug, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbQuizBySlug($language, $slug);

            CacheServerHelper::cachedData(
                $cacheKey,
                $record,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $key,
                    $this->mainTag,
                ]
            );
        }

        return $record;
    }

    public function getPreviousQuiz(string $key, Language $language, string $nowDate, ?int $cachedTTL = null): Quiz
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForPreviousQuiz($key, $this->secondKey, $nowDate, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbPreviousQuiz($language, $nowDate);

            CacheServerHelper::cachedData(
                $cacheKey,
                $record,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $key,
                    $this->mainTag,
                ]
            );
        }

        return $record;
    }


    public function getQuizzesByDate(string $key, Language $language, string $nowDate, Request|null $request = null, ?int $cachedTTL = null): Collection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForQuizzesByDate($key, $this->secondKey, $nowDate, $language, $request);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $records) {
            $records = $this->dbQuizzesByDate($language, $nowDate, $request);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $key,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }

    public function getQuizQuestionsByQuiz(string $key, Language $language, Quiz $quiz, ?Request $request = null, ?int $cachedTTL = null): LengthAwarePaginator
    {
        $perPage = 10;
        $cacheKey = CacheHelper::cacheKeyGenerateForQuizQuestionByQuiz($key, $this->secondKey, $quiz, $language, null, $perPage);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $records) {
            $records = $this->dbQuizQuestionByQuiz($language, $quiz, $request, $perPage);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $key,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }

    public function getQuizWinnerResultsByQuiz(string $key, Language $language, Quiz $quiz, ?int $cachedTTL = null)
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForQuizWinnerResultsByQuiz($key, $this->secondKey,  $quiz, $language);

        $quizWinners = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $quizWinners) {
            $record = $this->dbQuizWinnerResultsByQuiz($quiz);

            CacheServerHelper::cachedData(
                $cacheKey,
                $quizWinners,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $key,
                    $this->mainTag,
                ]
            );
        }

        return $record;
    }
}
