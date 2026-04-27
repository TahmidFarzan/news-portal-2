<?php
namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Tag;

class TagCacheService
{
    private int $cahedTime = 86400;
    private int $perPage   = 5000;

    protected array $baseTags = ['tag'];

    public function isConnected()
    {
        return CacheServerHelper::isConnected();
    }

    /* -------------------------------------------------
    | CLEAR CACHE
    |-------------------------------------------------*/

    public function clearCached()
    {
        CacheServerHelper::clearCachedByTag(['tag', 'public']);
        CacheServerHelper::clearCachedByTag(['tag', 'sitemap']);
    }

    /* -------------------------------------------------
    | DATABASE
    |-------------------------------------------------*/

    public function dbRecordsCount()
    {
        return Tag::count();
    }

    public function dbLastPageNo($perPage = null)
    {
        $perPage = $perPage ?? $this->perPage;
        return (int) ceil($this->dbRecordsCount() / $perPage);
    }

    private function dbRecords($perPage = null, $page = 1)
    {
        $perPage = $perPage ?? $this->perPage;

        return Tag::orderBy('id', 'asc')->with("language")->paginate($perPage, ['*'], 'page', $page);
    }

    /* -------------------------------------------------
    | CACHE WRITE
    |-------------------------------------------------*/

    public function cachedRecords($key, $perPage = null, $page = 1)
    {
        CacheServerHelper::cachedData(
            "tag {$key} page {$page}",
            $this->dbRecords($perPage, $page),
            $this->cahedTime,
            ['tag', $key]
        );
    }

    public function cachedRecordsCount($key)
    {
        CacheServerHelper::cachedData(
            "tag {$key} count",
            $this->dbRecordsCount(),
            $this->cahedTime,
            ['tag', $key]
        );
    }

    public function cachedLastPageNo($key)
    {
        CacheServerHelper::cachedData(
            "tag {$key} last page no",
            $this->dbLastPageNo(),
            $this->cahedTime,
            ['tag', $key]
        );
    }

    /* -------------------------------------------------
    | CACHE READ (WITH FALLBACK)
    |-------------------------------------------------*/

    public function recordsCount($key)
    {
        $cacheKey = "tag {$key} count";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['tag', $key]
        );

        if ($count === null) {
            $count = $this->dbRecordsCount();
            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cahedTime,
                ['tag', $key]
            );
        }

        return $count;
    }

    public function lastPageNo($key)
    {
        $cacheKey = "tag {$key} last page no";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['tag', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo();
            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cahedTime,
                ['tag', $key]
            );
        }

        return $lastPage;
    }

    public function records($key, $perPage = null, $page = 1)
    {
        $cacheKey = "tag {$key} page {$page}";

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            ['tag', $key]
        );

        if ($records === null) {
            $records = $this->dbRecords($perPage, $page);
            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $this->cahedTime,
                ['tag', $key]
            );
        }

        return $records;
    }
}
