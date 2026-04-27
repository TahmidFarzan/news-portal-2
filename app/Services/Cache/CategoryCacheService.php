<?php
namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Category;

class CategoryCacheService
{
    private int $cahedTime = 86400;
    private int $perPage   = 5000;

    protected array $baseTags = ['category'];

    public function isConnected()
    {
        return CacheServerHelper::isConnected();
    }

    /* -------------------------------------------------
    | CLEAR CACHE
    |-------------------------------------------------*/

    public function clearCached()
    {
        CacheServerHelper::clearCachedByTag(['category', 'public']);
        CacheServerHelper::clearCachedByTag(['category', 'sitemap']);
    }

    /* -------------------------------------------------
    | DATABASE
    |-------------------------------------------------*/

    public function dbRecordsCount()
    {
        return Category::count();
    }

    public function dbLastPageNo($perPage = null)
    {
        $perPage = $perPage ?? $this->perPage;
        return (int) ceil($this->dbRecordsCount() / $perPage);
    }

    private function dbRecords($perPage = null, $page = 1)
    {
        $perPage = $perPage ?? $this->perPage;

        return Category::orderBy('id', 'asc')->with("language")->paginate($perPage, ['*'], 'page', $page);
    }

    /* -------------------------------------------------
    | CACHE WRITE
    |-------------------------------------------------*/

    public function cachedRecords($key, $perPage = null, $page = 1)
    {
        CacheServerHelper::cachedData(
            "category {$key} page {$page}",
            $this->dbRecords($perPage, $page),
            $this->cahedTime,
            ['category', $key]
        );
    }

    public function cachedRecordsCount($key)
    {
        CacheServerHelper::cachedData(
            "category {$key} count",
            $this->dbRecordsCount(),
            $this->cahedTime,
            ['category', $key]
        );
    }

    public function cachedLastPageNo($key)
    {
        CacheServerHelper::cachedData(
            "category {$key} last page no",
            $this->dbLastPageNo(),
            $this->cahedTime,
            ['category', $key]
        );
    }

    /* -------------------------------------------------
    | CACHE READ (WITH FALLBACK)
    |-------------------------------------------------*/

    public function recordsCount($key)
    {
        $cacheKey = "category {$key} count";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['category', $key]
        );

        if ($count === null) {
            $count = $this->dbRecordsCount();
            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cahedTime,
                ['category', $key]
            );
        }

        return $count;
    }

    public function lastPageNo($key)
    {
        $cacheKey = "category {$key} last page no";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['category', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo();
            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cahedTime,
                ['category', $key]
            );
        }

        return $lastPage;
    }

    public function records($key, $perPage = null, $page = 1)
    {
        $cacheKey = "category {$key} page {$page}";

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            ['category', $key]
        );

        if ($records === null) {
            $records = $this->dbRecords($perPage, $page);
            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $this->cahedTime,
                ['category', $key]
            );
        }

        return $records;
    }
}
