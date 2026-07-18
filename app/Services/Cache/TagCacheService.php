<?php

namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Language;
use App\Models\Tag;
use App\Models\Trend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TagCacheService
{
    private int $perPage = 5000;

    private int $cachedTTL = 86400;

    private string $mainTag = CacheHelper::TAG_TAG;

    private string $secondKey = CacheHelper::KEY_TAG;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_PAGE]);
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_SITEMAP]);
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_FEED]);
    }

    private function getPerPage(?int $perPage = null): int
    {
        return $perPage ?? $this->perPage;
    }

    private function generalQueryRecords(Language $language): Builder
    {
        $records = Tag::query()->with('language');
        if ($language && $language?->id) {
            $records = $records->orderBy('id', 'asc');
        }

        return $records;
    }

    private function dbLastPageNo(Language $language, ?int $perPage = null): int
    {
        return (int) ceil($this->generalQueryRecords($language)->count() / $this->getPerPage($perPage));
    }

    private function dbRecords(Request $request, Language $language, ?int $perPage = null): LengthAwarePaginator
    {
        $records = Tag::query()->with('language');
        if ($language && $language?->id) {
            $records = $records->where('language_id', $language?->id);
        }

        $records = $records->paginate($this->getPerPage($request->input('per_page', $perPage)));

        return $records;
    }

    private function dbRecordsLimitForTrend(Language $language, ?int $perPage = null): Collection
    {
        return Trend::with([
            'tag',
        ])
            ->where('is_current', true)
            ->whereRelation('tag', 'language_id', $language->id)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'desc')
            ->limit($this->getPerPage($perPage))
            ->get();
    }

    private function dbRecordByIdOrSlug(Language $language, string|int $idOrSlug, ): Tag
    {
        $record = Tag::with(['language']);

        if ($language && $language?->id) {
            $record = $record->where('language_id', $language?->id);
        }

        $record = $record->where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->firstOrFail();

        return $record;
    }

    public function getLastPageNo(string $key, Language $language, ?int $perPage = null, ?int $cachedTTL = null): int
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForLastPageNo($key, $this->secondKey, $language);

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($language, $perPage);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $cachedTTL ?? $this->cachedTTL,
                [$this->mainTag, $key]
            );
        }

        return (int) $lastPage;
    }

    public function getRecords(string $key, Language $language, Request $request,  ?int $cachedTTL = null): LengthAwarePaginator
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForRecordsRequest($key, $this->secondKey, $request, $language);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $key]
        );

        if ($records === null) {
            $records = $this->dbRecords( $request, $language, );

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [$this->mainTag, $key]
            );
        }

        return $records;
    }

    public function getRecordsLimitForTrend(string $key, Language $language, ?int $perPage = null, ?int $cachedTTL = null): Collection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForRecordsLimitForTrend($key, $this->secondKey, $language, $perPage);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $key]
        );

        if ($records === null) {
            $records = $this->dbRecordsLimitForTrend($language, $perPage);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [$this->mainTag, $key]
            );
        }

        return $records;
    }

    public function getRecordById(string $key, Language $language, int|string $id,  ?int $cachedTTL = null): Tag
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordBySlug($key, $this->secondKey, $id, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByIdOrSlug($language, $id);

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

    public function getRecordBySlug(string $key, Language $language, string $slug,?int $cachedTTL = null): Tag
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
            $record = $this->dbRecordByIdOrSlug( $language, $slug,);

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
