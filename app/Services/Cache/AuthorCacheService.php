<?php
namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Author;

class AuthorCacheService
{
    private int $cahedTime = 86400;
    private int $perPage   = 5000;

    protected array $baseAuthors = ['author'];

    public function isConnected()
    {
        return CacheServerHelper::isConnected();
    }

    /* -------------------------------------------------
    | CLEAR CACHE
    |-------------------------------------------------*/

    public function clearCached()
    {
        CacheServerHelper::clearCachedByTag(['author', 'public']);
        CacheServerHelper::clearCachedByTag(['author', 'sitemap']);
    }

    /* -------------------------------------------------
    | DATABASE
    |-------------------------------------------------*/

    public function dbRecordsCount()
    {
        return Author::count();
    }

    public function dbLastPageNo($perPage = null)
    {
        $perPage = $perPage ?? $this->perPage;
        return (int) ceil($this->dbRecordsCount() / $perPage);
    }

    private function dbRecords($perPage = null, $page = 1)
    {
        $perPage = $perPage ?? $this->perPage;

        return Author::orderBy('id', 'asc')->with("language")->paginate($perPage, ['*'], 'page', $page);
    }

    /* -------------------------------------------------
    | CACHE WRITE
    |-------------------------------------------------*/

    public function cachedRecords($key, $perPage = null, $page = 1)
    {
        CacheServerHelper::cachedData(
            "author {$key} page {$page}",
            $this->dbRecords($perPage, $page),
            $this->cahedTime,
            ['author', $key]
        );
    }

    public function cachedRecordsCount($key)
    {
        CacheServerHelper::cachedData(
            "author {$key} count",
            $this->dbRecordsCount(),
            $this->cahedTime,
            ['author', $key]
        );
    }

    public function cachedLastPageNo($key)
    {
        CacheServerHelper::cachedData(
            "author {$key} last page no",
            $this->dbLastPageNo(),
            $this->cahedTime,
            ['author', $key]
        );
    }

    /* -------------------------------------------------
    | CACHE READ (WITH FALLBACK)
    |-------------------------------------------------*/

    public function recordsCount($key)
    {
        $cacheKey = "author {$key} count";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['author', $key]
        );

        if ($count === null) {
            $count = $this->dbRecordsCount();
            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cahedTime,
                ['author', $key]
            );
        }

        return $count;
    }

    public function lastPageNo($key)
    {
        $cacheKey = "author {$key} last page no";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['author', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo();
            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cahedTime,
                ['author', $key]
            );
        }

        return $lastPage;
    }

    public function records($key, $perPage = null, $page = 1)
    {
        $cacheKey = "author {$key} page {$page}";

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            ['author', $key]
        );

        if ($records === null) {
            $records = $this->dbRecords($perPage, $page);
            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $this->cahedTime,
                ['author', $key]
            );
        }

        return $records;
    }
}
