<?php

namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Contributor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class ContributorCacheService
{
    private int $cachedTime = 86400;
    private int $perPage = 5000;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag(['contributor', 'public']);
        CacheServerHelper::clearCachedByTag(['contributor', 'sitemap']);
    }

    private function getPerPage(array $filters = []): int
    {
        $perPage = (int) ($filters['per_page'] ?? $filters['perPage'] ?? $this->perPage);

        return $perPage > 0 ? $perPage : $this->perPage;
    }

    private function getPage(array $filters = []): int
    {
        $page = (int) ($filters['page'] ?? 1);

        return $page > 0 ? $page : 1;
    }

    private function normalizeFilters(array $filters = [], array $except = []): array
    {
        foreach ($except as $key) {
            unset($filters[$key]);
        }

        $filters = array_filter($filters, function ($value) {
            return $value !== null && $value !== '';
        });

        ksort($filters);

        return $filters;
    }

    private function filterHash(array $filters = [], array $except = []): string
    {
        $filters = $this->normalizeFilters($filters, $except);

        return md5(json_encode($filters));
    }

    private function queryContributors(array $filters = []): Builder
    {
        return Contributor::query()
            ->with('language')
            ->orderBy('id', 'asc');
    }

    public function dbContributorsCount(array $filters = []): int
    {
        return $this->queryContributors($filters)->count();
    }

    public function dbLastPageNo(array $filters = []): int
    {
        return (int) ceil($this->dbContributorsCount($filters) / $this->getPerPage($filters));
    }

    private function dbContributors(array $filters = []): LengthAwarePaginator
    {
        return $this->queryContributors($filters)->paginate(
            $this->getPerPage($filters),
            ['*'],
            'page',
            $this->getPage($filters)
        );
    }

    public function cachedContributors(string $key, array $filters = []): void
    {
        $page = $this->getPage($filters);
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "contributor {$key} page {$page} {$hash}",
            $this->dbContributors($filters),
            $this->cachedTime,
            ['contributor', $key]
        );
    }

    public function cachedContributorsCount(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        CacheServerHelper::cachedData(
            "contributor {$key} count {$hash}",
            $this->dbContributorsCount($filters),
            $this->cachedTime,
            ['contributor', $key]
        );
    }

    public function cachedLastPageNo(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "contributor {$key} last page no {$hash}",
            $this->dbLastPageNo($filters),
            $this->cachedTime,
            ['contributor', $key]
        );
    }

    public function contributorsCount(string $key, array $filters = []): int
    {
        $hash = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        $cacheKey = "contributor {$key} count {$hash}";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['contributor', $key]
        );

        if ($count === null) {
            $count = $this->dbContributorsCount($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cachedTime,
                ['contributor', $key]
            );
        }

        return (int) $count;
    }

    public function lastPageNo(string $key, array $filters = []): int
    {
        $hash = $this->filterHash($filters, ['page']);
        $cacheKey = "contributor {$key} last page no {$hash}";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['contributor', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cachedTime,
                ['contributor', $key]
            );
        }

        return (int) $lastPage;
    }

    public function contributors(string $key, array $filters = []): LengthAwarePaginator
    {
        $page = $this->getPage($filters);
        $hash = $this->filterHash($filters, ['page']);
        $cacheKey = "contributor {$key} page {$page} {$hash}";

        $contributors = CacheServerHelper::getCachedData(
            $cacheKey,
            ['contributor', $key]
        );

        if ($contributors === null) {
            $contributors = $this->dbContributors($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $contributors,
                $this->cachedTime,
                ['contributor', $key]
            );
        }

        return $contributors;
    }

    public function contributor(string $slug): Contributor
    {
        $cacheKey = "contributor slug tree {$slug}";

        $contributor = CacheServerHelper::getCachedData(
            $cacheKey,
            ['contributor', 'public']
        );

        if (!$contributor instanceof Contributor) {
            $contributor = Contributor::where('slug', $slug)->firstOrFail();

            CacheServerHelper::cachedData(
                $cacheKey,
                $contributor,
                $this->cachedTime,
                ['contributor', 'public']
            );
        }

        return $contributor;
    }
}
