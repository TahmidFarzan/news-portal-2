<?php

namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\News;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;

class NewsCacheService
{
    private int $cahedTime = 86400;
    private int $perPage = 5000;
    private int $latestRecordLimit = 1000;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag(['news', 'public']);
        CacheServerHelper::clearCachedByTag(['news', 'sitemap']);
    }

    public function dbNewsesCount(Request $request): int
    {
        return $this->dbNewsQuery($request)->count();
    }

    public function dbLastPageNo(Request $request): int
    {
        $perPage = $this->requestPerPage($request);

        return (int) ceil($this->dbNewsesCount($request) / $perPage);
    }

    public function dbNewses(Request $request): LengthAwarePaginator
    {
        $perPage = $this->requestPerPage($request);
        $page = $this->requestPage($request);

        return $this->dbNewsQuery($request)
            ->orderBy('id', 'desc')
            ->with('language')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function dbLatest(?int $latestRecordLimit = null): EloquentCollection
    {
        $latestRecordLimit = $latestRecordLimit ?? $this->latestRecordLimit;

        return News::where('is_published', true)
            ->orderBy('id', 'desc')
            ->take($latestRecordLimit)
            ->get();
    }

    public function cachedNewses(Request $request, string $key): void
    {
        CacheServerHelper::cachedData(
            $this->newsesCacheKey($request, $key),
            $this->dbNewses($request),
            $this->cahedTime,
            ['news', $key]
        );
    }

    public function cachedNewsesCount(Request $request, string $key): void
    {
        CacheServerHelper::cachedData(
            $this->countCacheKey($request, $key),
            $this->dbNewsesCount($request),
            $this->cahedTime,
            ['news', $key]
        );
    }

    public function cachedLastPageNo(Request $request, string $key): void
    {
        CacheServerHelper::cachedData(
            $this->lastPageCacheKey($request, $key),
            $this->dbLastPageNo($request),
            $this->cahedTime,
            ['news', $key]
        );
    }

    public function cachedLatest(string $cachedKey): void
    {
        $cachedKey = " news {$cachedKey} latest newses";
        $newses = $this->dbLatest();

        CacheServerHelper::cachedData($cachedKey, $newses, $this->cahedTime);
    }

    public function newsesCount(Request $request, string $key): int
    {
        $cacheKey = $this->countCacheKey($request, $key);

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($count === null) {
            $count = $this->dbNewsesCount($request);

            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cahedTime,
                ['news', $key]
            );
        }

        return (int) $count;
    }

    public function lastPageNo(Request $request, string $key): int
    {
        $cacheKey = $this->lastPageCacheKey($request, $key);

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($request);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cahedTime,
                ['news', $key]
            );
        }

        return (int) $lastPage;
    }

    public function newses(Request $request, string $key): LengthAwarePaginator
    {
        $cacheKey = $this->newsesCacheKey($request, $key);

        $newses = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($newses === null) {
            $newses = $this->dbNewses($request);

            CacheServerHelper::cachedData(
                $cacheKey,
                $newses,
                $this->cahedTime,
                ['news', $key]
            );
        }

        return $newses;
    }

    public function getLatest(string $cachedKey, ?int $latestRecordLimit = null): EloquentCollection|SupportCollection
    {
        $newses = null;
        $cachedKey = " news {$cachedKey} latest news";
        $redisConnected = CacheServerHelper::isConnected();

        if ($redisConnected) {
            $newses = CacheServerHelper::getCachedData($cachedKey);

            if (empty($newses)) {
                $newses = $this->dbLatest($latestRecordLimit);

                CacheServerHelper::cachedData(
                    $cachedKey,
                    $newses,
                    $this->cahedTime
                );
            }

            if (! empty($newses)) {
                $latestRecordLimit = ($latestRecordLimit !== null && $latestRecordLimit > $this->latestRecordLimit)
                    ? $latestRecordLimit
                    : $this->latestRecordLimit;

                $newses = collect($newses)->take($latestRecordLimit);
            }
        }

        if (! $redisConnected || empty($newses)) {
            $newses = $this->dbLatest($latestRecordLimit);
        }

        return $newses;
    }

    private function dbNewsQuery(Request $request): Builder
    {
        $newses = News::query()->where('is_published', true);

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

            $newses = $newses->whereHas('tags', function (Builder $relationQuery) use ($tagId): void {
                $relationQuery->where('id', $tagId);
            });
        }

        if ($request->filled('contributor_id')) {
            $contributorId = $request->input('contributor_id');

            $newses = $newses->whereHas('contributors', function (Builder $relationQuery) use ($contributorId): void {
                $relationQuery->where('id', $contributorId);
            });
        }

        return $newses;
    }

    private function requestPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', $this->perPage);

        return $perPage > 0 ? $perPage : $this->perPage;
    }

    private function requestPage(Request $request): int
    {
        $page = (int) $request->input('page', 1);

        return $page > 0 ? $page : 1;
    }

    private function requestFilterKey(Request $request): string
    {
        $filters = [
            'category_id' => $request->input('category_id'),
            'event_id' => $request->input('event_id'),
            'location_id' => $request->input('location_id'),
            'language_id' => $request->input('language_id'),
            'news_type_id' => $request->input('news_type_id'),
            'tag_id' => $request->input('tag_id'),
            'contributor_id' => $request->input('contributor_id'),
        ];

        $filters = array_filter($filters, function ($value): bool {
            return $value !== null && $value !== '';
        });

        if (empty($filters)) {
            return 'all';
        }

        ksort($filters);

        return md5(json_encode($filters));
    }

    private function hasRequestFilters(Request $request): bool
    {
        return $request->filled('category_id')
            || $request->filled('event_id')
            || $request->filled('location_id')
            || $request->filled('language_id')
            || $request->filled('news_type_id')
            || $request->filled('tag_id')
            || $request->filled('contributor_id');
    }

    private function countCacheKey(Request $request, string $key): string
    {
        if (! $this->hasRequestFilters($request)) {
            return "news {$key} count";
        }

        $filterKey = $this->requestFilterKey($request);

        return "news {$key} filter {$filterKey} count";
    }

    private function lastPageCacheKey(Request $request, string $key): string
    {
        if (! $this->hasRequestFilters($request) && ! $request->filled('per_page')) {
            return "news {$key} last page no";
        }

        $filterKey = $this->requestFilterKey($request);
        $perPage = $this->requestPerPage($request);

        return "news {$key} filter {$filterKey} per_page {$perPage} last page no";
    }

    private function newsesCacheKey(Request $request, string $key): string
    {
        $page = $this->requestPage($request);

        if (! $this->hasRequestFilters($request) && ! $request->filled('per_page')) {
            return "news {$key} page {$page}";
        }

        $filterKey = $this->requestFilterKey($request);
        $perPage = $this->requestPerPage($request);

        return "news {$key} filter {$filterKey} per_page {$perPage} page {$page}";
    }
}
