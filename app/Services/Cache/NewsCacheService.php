<?php

namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\News;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection as SupportCollection;

class NewsCacheService
{
    private int $cachedTime = 86400;
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

    public function dbNewsesCount(array $filters = []): int
    {
        return $this->dbNewsQuery($filters)->count();
    }

    public function dbLastPageNo(array $filters = []): int
    {
        $perPage = $this->perPage($filters);

        return (int) ceil($this->dbNewsesCount($filters) / $perPage);
    }

    public function dbNewses(array $filters = []): LengthAwarePaginator
    {
        $perPage = $this->perPage($filters);
        $page = $this->page($filters);

        return $this->dbNewsQuery($filters)
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

    public function cachedNewses(string $key, array $filters = []): void
    {
        CacheServerHelper::cachedData(
            $this->newsesCacheKey($key, $filters),
            $this->dbNewses($filters),
            $this->cachedTime,
            ['news', $key]
        );
    }

    public function cachedNewsesCount(string $key, array $filters = []): void
    {
        CacheServerHelper::cachedData(
            $this->countCacheKey($key, $filters),
            $this->dbNewsesCount($filters),
            $this->cachedTime,
            ['news', $key]
        );
    }

    public function cachedLastPageNo(string $key, array $filters = []): void
    {
        CacheServerHelper::cachedData(
            $this->lastPageCacheKey($key, $filters),
            $this->dbLastPageNo($filters),
            $this->cachedTime,
            ['news', $key]
        );
    }

    public function cachedLatest(string $cachedKey): void
    {
        $cacheKey = " news {$cachedKey} latest newses";
        $newses = $this->dbLatest();

        CacheServerHelper::cachedData(
            $cacheKey,
            $newses,
            $this->cachedTime
        );
    }

    public function newsesCount(string $key, array $filters = []): int
    {
        $cacheKey = $this->countCacheKey($key, $filters);

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($count === null) {
            $count = $this->dbNewsesCount($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cachedTime,
                ['news', $key]
            );
        }

        return (int) $count;
    }

    public function lastPageNo(string $key, array $filters = []): int
    {
        $cacheKey = $this->lastPageCacheKey($key, $filters);

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cachedTime,
                ['news', $key]
            );
        }

        return (int) $lastPage;
    }

    public function newses(string $key, array $filters = []): LengthAwarePaginator
    {
        $cacheKey = $this->newsesCacheKey($key, $filters);

        $newses = CacheServerHelper::getCachedData(
            $cacheKey,
            ['news', $key]
        );

        if ($newses === null) {
            $newses = $this->dbNewses($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $newses,
                $this->cachedTime,
                ['news', $key]
            );
        }

        return $newses;
    }

    public function getLatest(string $cachedKey, ?int $latestRecordLimit = null): EloquentCollection|SupportCollection
    {
        $newses = null;
        $cacheKey = " news {$cachedKey} latest news";
        $redisConnected = CacheServerHelper::isConnected();

        if ($redisConnected) {
            $newses = CacheServerHelper::getCachedData($cacheKey);

            if ($newses === null) {
                $newses = $this->dbLatest($latestRecordLimit);

                CacheServerHelper::cachedData(
                    $cacheKey,
                    $newses,
                    $this->cachedTime
                );
            }

            if ($newses !== null) {
                $limit = ($latestRecordLimit !== null && $latestRecordLimit > 0)
                    ? $latestRecordLimit
                    : $this->latestRecordLimit;

                return collect($newses)->take($limit);
            }
        }

        return $this->dbLatest($latestRecordLimit);
    }

    private function dbNewsQuery(array $filters = []): Builder
    {
        $newses = News::query()->where('is_published', true);

        if ($this->filled($filters, 'category_id')) {
            $newses = $newses->where('category_id', $filters['category_id']);
        }

        if ($this->filled($filters, 'event_id')) {
            $newses = $newses->where('event_id', $filters['event_id']);
        }

        if ($this->filled($filters, 'location_id')) {
            $newses = $newses->where('location_id', $filters['location_id']);
        }

        if ($this->filled($filters, 'language_id')) {
            $newses = $newses->where('language_id', $filters['language_id']);
        }

        if ($this->filled($filters, 'news_type_id')) {
            $newses = $newses->where('news_type_id', $filters['news_type_id']);
        }

        if ($this->filled($filters, 'tag_id')) {
            $tagId = $filters['tag_id'];

            $newses = $newses->whereHas('tags', function (Builder $relationQuery) use ($tagId): void {
                $relationQuery->where('id', $tagId);
            });
        }

        if ($this->filled($filters, 'contributor_id')) {
            $contributorId = $filters['contributor_id'];

            $newses = $newses->whereHas('contributors', function (Builder $relationQuery) use ($contributorId): void {
                $relationQuery->where('id', $contributorId);
            });
        }

        return $newses;
    }

    private function perPage(array $filters): int
    {
        $perPage = (int) ($filters['per_page'] ?? $this->perPage);

        return $perPage > 0 ? $perPage : $this->perPage;
    }

    private function page(array $filters): int
    {
        $page = (int) ($filters['page'] ?? 1);

        return $page > 0 ? $page : 1;
    }

    private function filterKey(array $filters): string
    {
        $filterData = [];

        foreach ($this->filterableKeys() as $key) {
            if ($this->filled($filters, $key)) {
                $filterData[$key] = $filters[$key];
            }
        }

        if (empty($filterData)) {
            return 'all';
        }

        ksort($filterData);

        return md5(json_encode($filterData));
    }

    private function hasFilters(array $filters): bool
    {
        foreach ($this->filterableKeys() as $key) {
            if ($this->filled($filters, $key)) {
                return true;
            }
        }

        return false;
    }

    private function filled(array $filters, string $key): bool
    {
        if (! array_key_exists($key, $filters)) {
            return false;
        }

        $value = $filters[$key];

        if (is_array($value)) {
            return count($value) > 0;
        }

        return $value !== null && $value !== '';
    }

    private function filterableKeys(): array
    {
        return [
            'category_id',
            'event_id',
            'location_id',
            'language_id',
            'news_type_id',
            'tag_id',
            'contributor_id',
        ];
    }

    private function countCacheKey(string $key, array $filters): string
    {
        if (! $this->hasFilters($filters)) {
            return "news {$key} count";
        }

        $filterKey = $this->filterKey($filters);

        return "news {$key} filter {$filterKey} count";
    }

    private function lastPageCacheKey(string $key, array $filters): string
    {
        if (! $this->hasFilters($filters) && ! $this->filled($filters, 'per_page')) {
            return "news {$key} last page no";
        }

        $filterKey = $this->filterKey($filters);
        $perPage = $this->perPage($filters);

        return "news {$key} filter {$filterKey} per_page {$perPage} last page no";
    }

    private function newsesCacheKey(string $key, array $filters): string
    {
        $page = $this->page($filters);

        if (! $this->hasFilters($filters) && ! $this->filled($filters, 'per_page')) {
            return "news {$key} page {$page}";
        }

        $filterKey = $this->filterKey($filters);
        $perPage = $this->perPage($filters);

        return "news {$key} filter {$filterKey} per_page {$perPage} page {$page}";
    }
}
