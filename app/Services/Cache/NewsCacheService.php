<?php
namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\News;

class NewsCacheService
{
    private int $cahedTime = 86400;
    private int $perPage   = 5000;
    private $latestRecordLimit   = 1000;

    public function isConnected()
    {
        return CacheServerHelper::isConnected();
    }

    /* -------------------------------------------------
    | CLEAR CACHE
    |-------------------------------------------------*/

    public function clearCached()
    {
        CacheServerHelper::clearCachedByTag(['news', 'public']);
        CacheServerHelper::clearCachedByTag(['news', 'sitemap']);
    }

    /* -------------------------------------------------
    | DATABASE
    |-------------------------------------------------*/

    public function dbNewsesCount()
    {
        return News::where("is_published", true)->count();
    }

    public function dbLastPageNo($perPage = null)
    {
        $perPage = $perPage ?? $this->perPage;
        return (int) ceil($this->dbNewsesCount() / $perPage);
    }

    private function dbNewses($perPage = null, $page = 1)
    {
        $perPage = $perPage ?? $this->perPage;

        return News::where("is_published", true)->orderBy('id', 'desc')->with("language")->paginate($perPage, ['*'], 'page', $page);
    }

    public function dbLatest($latestRecordLimit = null)
    {
        $currentDate = now();
        $startDate   = $currentDate->copy()->subDays(3);
        $latestRecordLimit = $latestRecordLimit ?? $this->latestRecordLimit;

        $newses = News::where("is_published", true)->orderBy("id", "desc");
        $newses = $newses->take($latestRecordLimit);
        $newses = $newses->get();
        return $newses;
    }

    /* -------------------------------------------------
    | CACHE WRITE
    |-------------------------------------------------*/

    public function cachedNewses($key, $perPage = null, $page = 1)
    {
        CacheServerHelper::cachedData(
            "news {$key} page {$page}",
            $this->dbNewses($perPage, $page),
            $this->cahedTime,
            ['news', $key]
        );
    }

    public function cachedNewsesCount($key)
    {
        CacheServerHelper::cachedData(
            "news {$key} count",
            $this->dbNewsesCount(),
            $this->cahedTime,
            ['news', $key]
        );
    }

    public function cachedLastPageNo($key)
    {
        CacheServerHelper::cachedData(
            "news {$key} last page no",
            $this->dbLastPageNo(),
            $this->cahedTime,
            ['news', $key]
        );
    }

    public function cachedLatest($cachedKey)
    {
        $cachedKey = " news {$cachedKey} latest newses";
        $newses   = self::dbLatest(null);
        CacheServerHelper::cachedData($cachedKey, $newses, $this->cahedTime);
    }

    /* -------------------------------------------------
    | CACHE READ (WITH FALLBACK)
    |-------------------------------------------------*/

    public function newsesCount($key)
    {
        $cacheKey = "news {$key} count";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($count === null) {
            $count = $this->dbNewsesCount();
            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cahedTime,
                ['news', $key]
            );
        }

        return $count;
    }

    public function lastPageNo($key)
    {
        $cacheKey = "news {$key} last page no";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo();
            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cahedTime,
                ['news', $key]
            );
        }

        return $lastPage;
    }

    public function newses($key, $perPage = null, $page = 1)
    {
        $cacheKey = "news {$key} page {$page}";

        $newses = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($newses === null) {
            $newses = $this->dbNewses($perPage, $page);
            CacheServerHelper::cachedData(
                $cacheKey,
                $newses,
                $this->cahedTime,
                ['news', $key]
            );
        }

        return $newses;
    }

    public function getLatest($cachedKey, $latestRecordLimit = null)
    {
        $newses        = null;
        $cachedKey      = " news {$cachedKey} latest news";
        $redisConnected = CacheServerHelper::isConnected();

        if ($redisConnected) {
            $newses = CacheServerHelper::getCachedData($cachedKey);

            if (empty($newses)) {
                $newses = self::dbLatest($latestRecordLimit);
                CacheServerHelper::cachedData($cachedKey, $newses, $this->cahedTime);
            }

            if (! empty($newses)) {
                $latestRecordLimit = ($latestRecordLimit > $this->latestRecordLimit) ? $latestRecordLimit : $this->latestRecordLimit;
                $newses     = collect($newses)->take($latestRecordLimit);
            }
        }
        if (! $redisConnected || (empty($newses) || ($newses == null))) {
            $newses = self::dbLatest($latestRecordLimit);
        }
        return $newses;
    }
}
