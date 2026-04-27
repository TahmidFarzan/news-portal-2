<?php
namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Location;

class LocationCacheService
{
    private int $cahedTime = 86400;
    private int $perPage   = 5000;

    protected array $baseTags = ['location'];

    public function isConnected()
    {
        return CacheServerHelper::isConnected();
    }

    /* -------------------------------------------------
    | CLEAR CACHE
    |-------------------------------------------------*/

    public function clearCached()
    {
        CacheServerHelper::clearCachedByTag(['location', 'public']);
        CacheServerHelper::clearCachedByTag(['location', 'sitemap']);
    }

    /* -------------------------------------------------
    | DATABASE
    |-------------------------------------------------*/

    public function dbRecordsCount()
    {
        return Location::count();
    }

    public function dbLastPageNo($perPage = null)
    {
        $perPage = $perPage ?? $this->perPage;
        return (int) ceil($this->dbRecordsCount() / $perPage);
    }

    private function dbRecords($perPage = null, $page = 1)
    {
        $perPage = $perPage ?? $this->perPage;

        return Location::orderBy('id', 'asc')->with("language")->paginate($perPage, ['*'], 'page', $page);
    }

    /* -------------------------------------------------
    | CACHE WRITE
    |-------------------------------------------------*/

    public function cachedRecords($key, $perPage = null, $page = 1)
    {
        CacheServerHelper::cachedData(
            "location {$key} page {$page}",
            $this->dbRecords($perPage, $page),
            $this->cahedTime,
            ['location', $key]
        );
    }

    public function cachedRecordsCount($key)
    {
        CacheServerHelper::cachedData(
            "location {$key} count",
            $this->dbRecordsCount(),
            $this->cahedTime,
            ['location', $key]
        );
    }

    public function cachedLastPageNo($key)
    {
        CacheServerHelper::cachedData(
            "location {$key} last page no",
            $this->dbLastPageNo(),
            $this->cahedTime,
            ['location', $key]
        );
    }

    /* -------------------------------------------------
    | CACHE READ (WITH FALLBACK)
    |-------------------------------------------------*/

    public function recordsCount($key)
    {
        $cacheKey = "location {$key} count";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['location', $key]
        );

        if ($count === null) {
            $count = $this->dbRecordsCount();
            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cahedTime,
                ['location', $key]
            );
        }

        return $count;
    }

    public function lastPageNo($key)
    {
        $cacheKey = "location {$key} last page no";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['location', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo();
            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cahedTime,
                ['location', $key]
            );
        }

        return $lastPage;
    }

    public function records($key, $perPage = null, $page = 1)
    {
        $cacheKey = "location {$key} page {$page}";

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            ['location', $key]
        );

        if ($records === null) {
            $records = $this->dbRecords($perPage, $page);
            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $this->cahedTime,
                ['location', $key]
            );
        }

        return $records;
    }
}
