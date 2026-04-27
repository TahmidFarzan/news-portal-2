<?php
namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Event;

class EventCacheService
{
    private int $cahedTime = 86400;
    private int $perPage   = 5000;

    protected array $baseTags = ['event'];

    public function isConnected()
    {
        return CacheServerHelper::isConnected();
    }

    /* -------------------------------------------------
    | CLEAR CACHE
    |-------------------------------------------------*/

    public function clearCached()
    {
        CacheServerHelper::clearCachedByTag(['event', 'public']);
        CacheServerHelper::clearCachedByTag(['event', 'sitemap']);
    }

    /* -------------------------------------------------
    | DATABASE
    |-------------------------------------------------*/

    public function dbRecordsCount()
    {
        return Event::count();
    }

    public function dbLastPageNo($perPage = null)
    {
        $perPage = $perPage ?? $this->perPage;
        return (int) ceil($this->dbRecordsCount() / $perPage);
    }

    private function dbRecords($perPage = null, $page = 1)
    {
        $perPage = $perPage ?? $this->perPage;

        return Event::orderBy('id', 'asc')->with("language")->paginate($perPage, ['*'], 'page', $page);
    }

    /* -------------------------------------------------
    | CACHE WRITE
    |-------------------------------------------------*/

    public function cachedRecords($key, $perPage = null, $page = 1)
    {
        CacheServerHelper::cachedData(
            "event {$key} page {$page}",
            $this->dbRecords($perPage, $page),
            $this->cahedTime,
            ['event', $key]
        );
    }

    public function cachedRecordsCount($key)
    {
        CacheServerHelper::cachedData(
            "event {$key} count",
            $this->dbRecordsCount(),
            $this->cahedTime,
            ['event', $key]
        );
    }

    public function cachedLastPageNo($key)
    {
        CacheServerHelper::cachedData(
            "event {$key} last page no",
            $this->dbLastPageNo(),
            $this->cahedTime,
            ['event', $key]
        );
    }

    /* -------------------------------------------------
    | CACHE READ (WITH FALLBACK)
    |-------------------------------------------------*/

    public function recordsCount($key)
    {
        $cacheKey = "event {$key} count";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['event', $key]
        );

        if ($count === null) {
            $count = $this->dbRecordsCount();
            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cahedTime,
                ['event', $key]
            );
        }

        return $count;
    }

    public function lastPageNo($key)
    {
        $cacheKey = "event {$key} last page no";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['event', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo();
            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cahedTime,
                ['event', $key]
            );
        }

        return $lastPage;
    }

    public function records($key, $perPage = null, $page = 1)
    {
        $cacheKey = "event {$key} page {$page}";

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            ['event', $key]
        );

        if ($records === null) {
            $records = $this->dbRecords($perPage, $page);
            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $this->cahedTime,
                ['event', $key]
            );
        }

        return $records;
    }
}
