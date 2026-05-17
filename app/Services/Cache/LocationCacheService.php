<?php

namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class LocationCacheService
{
    private int $cachedTime = 86400;
    private int $perPage = 5000;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag(['location', 'public']);
        CacheServerHelper::clearCachedByTag(['location', 'sitemap']);
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

    private function queryLocations(array $filters = []): Builder
    {
        return Location::query()
            ->with('language')
            ->orderBy('id', 'asc');
    }

    public function dbLocationsCount(array $filters = []): int
    {
        return $this->queryLocations($filters)->count();
    }

    public function dbLastPageNo(array $filters = []): int
    {
        return (int) ceil($this->dbLocationsCount($filters) / $this->getPerPage($filters));
    }

    private function dbLocations(array $filters = []): LengthAwarePaginator
    {
        return $this->queryLocations($filters)->paginate(
            $this->getPerPage($filters),
            ['*'],
            'page',
            $this->getPage($filters)
        );
    }

    public function cachedLocations(string $key, array $filters = []): void
    {
        $page = $this->getPage($filters);
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "location {$key} page {$page} {$hash}",
            $this->dbLocations($filters),
            $this->cachedTime,
            ['location', $key]
        );
    }

    public function cachedLocationsCount(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        CacheServerHelper::cachedData(
            "location {$key} count {$hash}",
            $this->dbLocationsCount($filters),
            $this->cachedTime,
            ['location', $key]
        );
    }

    public function cachedLastPageNo(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "location {$key} last page no {$hash}",
            $this->dbLastPageNo($filters),
            $this->cachedTime,
            ['location', $key]
        );
    }

    public function locationsCount(string $key, array $filters = []): int
    {
        $hash = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        $cacheKey = "location {$key} count {$hash}";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['location', $key]
        );

        if ($count === null) {
            $count = $this->dbLocationsCount($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cachedTime,
                ['location', $key]
            );
        }

        return (int) $count;
    }

    public function lastPageNo(string $key, array $filters = []): int
    {
        $hash = $this->filterHash($filters, ['page']);
        $cacheKey = "location {$key} last page no {$hash}";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['location', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cachedTime,
                ['location', $key]
            );
        }

        return (int) $lastPage;
    }

    public function locations(string $key, array $filters = []): LengthAwarePaginator
    {
        $page = $this->getPage($filters);
        $hash = $this->filterHash($filters, ['page']);
        $cacheKey = "location {$key} page {$page} {$hash}";

        $locations = CacheServerHelper::getCachedData(
            $cacheKey,
            ['location', $key]
        );

        if ($locations === null) {
            $locations = $this->dbLocations($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $locations,
                $this->cachedTime,
                ['location', $key]
            );
        }

        return $locations;
    }

    public function locationBySlugTree(string $slugTree): Location
    {
        $cacheKey = "location slug tree {$slugTree}";

        $location = CacheServerHelper::getCachedData(
            $cacheKey,
            ['location', 'public']
        );

        if (!$location instanceof Location) {
            $location = Location::where('slug_tree', $slugTree)->firstOrFail();

            CacheServerHelper::cachedData(
                $cacheKey,
                $location,
                $this->cachedTime,
                ['location', 'public']
            );
        }

        return $location;
    }
}
