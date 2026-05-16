<?php
namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\News;
use Illuminate\Http\Request;

class NewsCacheService
{
    private int $cahedTime     = 86400;
    private int $perPage       = 5000;
    private $latestRecordLimit = 1000;

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
        $currentDate       = now();
        $startDate         = $currentDate->copy()->subDays(3);
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
        $newses    = self::dbLatest(null);
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
        $newses         = null;
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
                $newses            = collect($newses)->take($latestRecordLimit);
            }
        }
        if (! $redisConnected || (empty($newses) || ($newses == null))) {
            $newses = self::dbLatest($latestRecordLimit);
        }
        return $newses;
    }

    public function dbNewsesCountAccrodingRequest(Request $request)
    {
        return $this->dbNewsQueryAccrodingRequest($request)->count();
    }

    public function dbLastPageNoAccrodingRequest(Request $request)
    {
        $perPage = $this->requestPerPage($request);

        return (int) ceil($this->dbNewsesCountAccrodingRequest($request) / $perPage);
    }

    public function cachedNewsesCountAccrodingRequest($key, Request $request)
    {
        CacheServerHelper::cachedData(
            $this->requestCountCacheKey($key, $request),
            $this->dbNewsesCountAccrodingRequest($request),
            $this->cahedTime,
            ['news', $key]
        );
    }

    public function cachedLastPageNoAccrodingRequest($key, Request $request)
    {
        CacheServerHelper::cachedData(
            $this->requestLastPageCacheKey($key, $request),
            $this->dbLastPageNoAccrodingRequest($request),
            $this->cahedTime,
            ['news', $key]
        );
    }

    public function cachedNewsesAccrodingRequest($key, Request $request)
    {
        CacheServerHelper::cachedData(
            $this->requestNewsesCacheKey($key, $request),
            $this->dbNewsesAccrodingRequest($request),
            $this->cahedTime,
            ['news', $key]
        );
    }

    public function newsesCountAccrodingRequest($key, Request $request)
    {
        $cacheKey = $this->requestCountCacheKey($key, $request);

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($count === null) {
            $count = $this->dbNewsesCountAccrodingRequest($request);

            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cahedTime,
                ['news', $key]
            );
        }

        return $count;
    }

    public function lastPageNoAccrodingRequest($key, Request $request)
    {
        $cacheKey = $this->requestLastPageCacheKey($key, $request);

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNoAccrodingRequest($request);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cahedTime,
                ['news', $key]
            );
        }

        return $lastPage;
    }

    public function newsesAccrodingRequest($key,Request $request)
    {
        $cacheKey = $this->requestNewsesCacheKey($key, $request);

        $newses = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($newses === null) {
            $newses = $this->dbNewsesAccrodingRequest($request);

            CacheServerHelper::cachedData(
                $cacheKey,
                $newses,
                $this->cahedTime,
                ['news', $key]
            );
        }

        return $newses;
    }

    private function requestPerPage(Request $request)
    {
        $perPage = (int) $request->input('per_page', $this->perPage);

        return $perPage > 0 ? $perPage : $this->perPage;
    }

    private function requestPage(Request $request)
    {
        $page = (int) $request->input('page', 1);

        return $page > 0 ? $page : 1;
    }

    private function requestFilterKey(Request $request)
    {
        $filters = [
            'category_id'    => $request->input('category_id'),
            'event_id'       => $request->input('event_id'),
            'location_id'    => $request->input('location_id'),
            'language_id'    => $request->input('language_id'),
            'news_type_id'   => $request->input('news_type_id'),
            'tag_id'         => $request->input('tag_id'),
            'contributor_id' => $request->input('contributor_id'),
        ];

        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        return md5(json_encode($filters));
    }

    private function dbNewsQueryAccrodingRequest(Request $request)
    {
        $newses = News::query();

        if ($request->filled('category_id')) {
            $newses = $newses->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('event_id')) {
            $newses = $newses->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('location_id')) {
            $newses = $newses->where('location_id', $request->input('location_id'));
        }

        if ($request->filled('language_id')) {
            $newses = $newses->where('language_id', $request->input('language_id'));
        }

        if ($request->filled('news_type_id')) {
            $newses = $newses->where('news_type_id', $request->input('news_type_id'));
        }

        if ($request->filled('tag_id')) {
            $tagId = $request->input('tag_id');

            $newses = $newses->whereHas('tags', function ($relationQuery) use ($tagId) {
                $relationQuery->where('id', $tagId);
            });
        }

        if ($request->filled('contributor_id')) {
            $contributorId = $request->input('contributor_id');

            $newses = $newses->whereHas('contributors', function ($relationQuery) use ($contributorId) {
                $relationQuery->where('id', $contributorId);
            });
        }

        return $newses->where("is_published", true);
    }

    private function dbNewsesAccrodingRequest(Request $request)
    {
        $perPage = $this->requestPerPage($request);
        $page    = $this->requestPage($request);

        return $this->dbNewsQueryAccrodingRequest($request)
            ->orderBy('id', 'desc')
            ->with("language")
            ->paginate($perPage, ['*'], 'page', $page);
    }

    private function requestCountCacheKey($key, Request $request)
    {
        $filterKey = $this->requestFilterKey($request);

        return "news {$key} filter {$filterKey} count";
    }

    private function requestLastPageCacheKey($key, Request $request)
    {
        $filterKey = $this->requestFilterKey($request);
        $perPage   = $this->requestPerPage($request);

        return "news {$key} filter {$filterKey} per_page {$perPage} last page no";
    }

    private function requestNewsesCacheKey($key, Request $request)
    {
        $filterKey = $this->requestFilterKey($request);
        $perPage   = $this->requestPerPage($request);
        $page      = $this->requestPage($request);

        return "news {$key} filter {$filterKey} per_page {$perPage} page {$page}";
    }

}
