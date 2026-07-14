<?php
namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Language;
use App\Models\Survey;
use App\Models\SurveyQuestion;

class SurveyCacheService
{
    private int $cachedTTL = 300;

    private string $mainTag   = CacheHelper::TAG_SURVEY;
    private string $secondKey = CacheHelper::KEY_SURVEY;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_PAGE]);
    }

    private function dbSurveyBySlug(string $slug, ?Language $language = null): Survey
    {
        $record = Survey::query()->with([
            "surveyQuestions",
            "surveyQuestions.surveyQuestionResult",
        ])
            ->where("is_active", true);

        if ($language && $language?->id) {
            $record = $record->where("language_id", $language?->id);
        }

        $record = $record->where('slug', $slug)->firstOrFail();

        return $record;
    }

    private function dbSurveyByDate($nowDate, ?Language $language = null)
    {
        $records = Survey::query()->with([
            "surveyQuestions",
            "surveyQuestions.surveyQuestionResult",
        ])
            ->whereDate('date', $nowDate)
            ->where("is_active", true);

        if ($language && $language?->id) {
            $records = $records->where("language_id", $language?->id);
        }

        $records = $records->get();

        return $records;
    }

    private function dbSurveyQuestionByQuestion(Survey $survey, string $slug)
    {
        return SurveyQuestion::query()
            ->where("survey_id", $survey->id)
            ->where("slug", $slug)
            ->firstOrFail();
    }

    public function getSurveyBySlug(string $key, string $slug, ?Language $language = null, int | null $cachedTTL = null): Survey
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
            $record = $this->dbSurveyBySlug($slug, $language);

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

    public function getRecordsByDate(string $key, $nowDate, ?Language $language = null, int | null $cachedTTL = null)
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSurveysByDate($key, $this->secondKey, $nowDate, $language);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $records) {
            $records = $this->dbSurveyByDate($nowDate, $language);

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

    public function getSurveyQuestionByQuestion(string $key, Survey $survey, string $slug, ?Language $language = null, int | null $cachedTTL = null)
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSurveyQuestionBySlugForSurvey($key, $this->secondKey, $survey, $slug, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbSurveyQuestionByQuestion($survey, $slug);

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
}
