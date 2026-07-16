<?php
namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Language;
use App\Models\NewsType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class NewsTypeCacheService
{
    private int $perPage   = 5000;
    private int $cachedTTL = 86400;

    private string $mainTag   = CacheHelper::TAG_NEWS_TYPE;
    private string $secondKey = CacheHelper::KEY_NEWS_TYPE;

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

    private function getPerPage(int | null $perPage = null): int
    {
        return $perPage ?? $this->perPage;
    }

    private function generalQueryRecords(): Builder
    {
        return NewsType::query()->orderBy('id', 'asc');
    }

    private function dbLastPageNo(int | null $perPage = null): int
    {
        return (int) ceil($this->generalQueryRecords()->count() / $this->getPerPage($perPage));
    }

    private function dbRecords(Request $request, int | null $perPage = null): LengthAwarePaginator
    {
        return NewsType::query()->orderBy('id', 'asc')->paginate($this->getPerPage($request->input("per_page", $perPage)));;
    }

    private function dbRecordByIdOrSlug(string | int $idOrSlug): NewsType
    {
        return NewsType::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->firstOrFail();
    }

    public function getLastPageNo(string $key, int | null $perPage = null, int | null $cachedTTL = null): int
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForLastPageNo($key, $this->secondKey);

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($perPage);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $cachedTTL ?? $this->cachedTTL,
                [$this->mainTag, $key]
            );
        }

        return (int) $lastPage;
    }

    public function getRecords(string $key, Request $request, int | null $cachedTTL = null): LengthAwarePaginator
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForRecordsRequest($key, $this->secondKey, $request);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $key]
        );

        if ($records === null) {
            $records = $this->dbRecords($request);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [$this->mainTag, $key]
            );
        }

        return $records;
    }

    public function getRecordById(string $key, int | string $id, int | null $cachedTTL = null): NewsType
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordBySlug($key, $this->secondKey, $id);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByIdOrSlug($id);

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

    public function getRecordBySlug(string $key, string $slug, int | null $cachedTTL = null): NewsType
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordBySlug($key, $this->secondKey, $slug);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByIdOrSlug($slug);

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
