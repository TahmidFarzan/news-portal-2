<?php
namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\News;

class NewsCacheService
{
    private int $cahedTime = 86400;
    private int $perPage   = 5000;
    private $latestRecordLimit   = 1000;

    protected array $baseNewss = ['news'];

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

    public function dbRecordsCount()
    {
        return News::where("is_published", true)->count();
    }

    public function dbLastPageNo($perPage = null)
    {
        $perPage = $perPage ?? $this->perPage;
        return (int) ceil($this->dbRecordsCount() / $perPage);
    }

    private function dbRecords($perPage = null, $page = 1)
    {
        $perPage = $perPage ?? $this->perPage;

        return News::where("is_published", true)->orderBy('id', 'desc')->with("language")->paginate($perPage, ['*'], 'page', $page);
    }

    public function dbLatest($latestRecordLimit = null)
    {
        $currentDate = now();
        $startDate   = $currentDate->copy()->subDays(3);
        $latestRecordLimit = $latestRecordLimit ?? $this->latestRecordLimit;

        $records = News::where("is_published", true)->orderBy("id", "desc");
        $records = $records->take($latestRecordLimit);
        $records = $records->get();
        return $records;
    }

    /* -------------------------------------------------
    | CACHE WRITE
    |-------------------------------------------------*/

    public function cachedRecords($key, $perPage = null, $page = 1)
    {
        CacheServerHelper::cachedData(
            "news {$key} page {$page}",
            $this->dbRecords($perPage, $page),
            $this->cahedTime,
            ['news', $key]
        );
    }

    public function cachedRecordsCount($key)
    {
        CacheServerHelper::cachedData(
            "news {$key} count",
            $this->dbRecordsCount(),
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
        $cachedKey = " news {$cachedKey} latest stories";
        $records   = self::dbLatest(null);
        CacheServerHelper::cachedData($cachedKey, $records, $this->cahedTime);
    }

    /* -------------------------------------------------
    | CACHE READ (WITH FALLBACK)
    |-------------------------------------------------*/

    public function recordsCount($key)
    {
        $cacheKey = "news {$key} count";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($count === null) {
            $count = $this->dbRecordsCount();
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

    public function records($key, $perPage = null, $page = 1)
    {
        $cacheKey = "news {$key} page {$page}";

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($records === null) {
            $records = $this->dbRecords($perPage, $page);
            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $this->cahedTime,
                ['news', $key]
            );
        }

        return $records;
    }

    public function getLatest($cachedKey, $latestRecordLimit = null)
    {
        $records        = null;
        $cachedKey      = " news {$cachedKey} latest news";
        $redisConnected = CacheServerHelper::isConnected();

        if ($redisConnected) {
            $records = CacheServerHelper::getCachedData($cachedKey);

            if (empty($records)) {
                $records = self::dbLatest($latestRecordLimit);
                CacheServerHelper::cachedData($cachedKey, $records, $this->cahedTime);
            }

            if (! empty($records)) {
                $latestRecordLimit = ($latestRecordLimit > $this->latestRecordLimit) ? $latestRecordLimit : $this->latestRecordLimit;
                $records     = collect($records)->take($latestRecordLimit);
            }
        }
        if (! $redisConnected || (empty($records) || ($records == null))) {
            $records = self::dbLatest($latestRecordLimit);
        }
        return $records;
    }
}
