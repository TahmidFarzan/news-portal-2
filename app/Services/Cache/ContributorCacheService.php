<?php
namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Contributor;

class ContributorCacheService
{
    private int $cahedTime = 86400;
    private int $perPage   = 5000;

    protected array $baseContributors = ['contributor'];

    public function isConnected()
    {
        return CacheServerHelper::isConnected();
    }

    /* -------------------------------------------------
    | CLEAR CACHE
    |-------------------------------------------------*/

    public function clearCached()
    {
        CacheServerHelper::clearCachedByTag(['contributor', 'public']);
        CacheServerHelper::clearCachedByTag(['contributor', 'sitemap']);
    }

    /* -------------------------------------------------
    | DATABASE
    |-------------------------------------------------*/

    public function dbRecordsCount()
    {
        return Contributor::count();
    }

    public function dbLastPageNo($perPage = null)
    {
        $perPage = $perPage ?? $this->perPage;
        return (int) ceil($this->dbRecordsCount() / $perPage);
    }

    private function dbRecords($perPage = null, $page = 1)
    {
        $perPage = $perPage ?? $this->perPage;

        return Contributor::orderBy('id', 'asc')->with("language")->paginate($perPage, ['*'], 'page', $page);
    }

    /* -------------------------------------------------
    | CACHE WRITE
    |-------------------------------------------------*/

    public function cachedRecords($key, $perPage = null, $page = 1)
    {
        CacheServerHelper::cachedData(
            "contributor {$key} page {$page}",
            $this->dbRecords($perPage, $page),
            $this->cahedTime,
            ['contributor', $key]
        );
    }

    public function cachedRecordsCount($key)
    {
        CacheServerHelper::cachedData(
            "contributor {$key} count",
            $this->dbRecordsCount(),
            $this->cahedTime,
            ['contributor', $key]
        );
    }

    public function cachedLastPageNo($key)
    {
        CacheServerHelper::cachedData(
            "contributor {$key} last page no",
            $this->dbLastPageNo(),
            $this->cahedTime,
            ['contributor', $key]
        );
    }

    /* -------------------------------------------------
    | CACHE READ (WITH FALLBACK)
    |-------------------------------------------------*/

    public function recordsCount($key)
    {
        $cacheKey = "contributor {$key} count";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['contributor', $key]
        );

        if ($count === null) {
            $count = $this->dbRecordsCount();
            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cahedTime,
                ['contributor', $key]
            );
        }

        return $count;
    }

    public function lastPageNo($key)
    {
        $cacheKey = "contributor {$key} last page no";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['contributor', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo();
            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cahedTime,
                ['contributor', $key]
            );
        }

        return $lastPage;
    }

    public function records($key, $perPage = null, $page = 1)
    {
        $cacheKey = "contributor {$key} page {$page}";

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            ['contributor', $key]
        );

        if ($records === null) {
            $records = $this->dbRecords($perPage, $page);
            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $this->cahedTime,
                ['contributor', $key]
            );
        }

        return $records;
    }
}
