<?php

namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class CategoryCacheService
{
    private int $cachedTime = 86400;
    private int $perPage = 5000;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag(['category', 'public']);
        CacheServerHelper::clearCachedByTag(['category', 'sitemap']);
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

    private function queryCategories(array $filters = []): Builder
    {
        return Category::query()
            ->with('language')
            ->orderBy('id', 'asc');
    }

    public function dbCategoriesCount(array $filters = []): int
    {
        return $this->queryCategories($filters)->count();
    }

    public function dbLastPageNo(array $filters = []): int
    {
        return (int) ceil($this->dbCategoriesCount($filters) / $this->getPerPage($filters));
    }

    private function dbCategories(array $filters = []): LengthAwarePaginator
    {
        return $this->queryCategories($filters)->paginate(
            $this->getPerPage($filters),
            ['*'],
            'page',
            $this->getPage($filters)
        );
    }

    public function cachedCategories(string $key, array $filters = []): void
    {
        $page = $this->getPage($filters);
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "category {$key} page {$page} {$hash}",
            $this->dbCategories($filters),
            $this->cachedTime,
            ['category', $key]
        );
    }

    public function cachedCategoriesCount(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        CacheServerHelper::cachedData(
            "category {$key} count {$hash}",
            $this->dbCategoriesCount($filters),
            $this->cachedTime,
            ['category', $key]
        );
    }

    public function cachedLastPageNo(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "category {$key} last page no {$hash}",
            $this->dbLastPageNo($filters),
            $this->cachedTime,
            ['category', $key]
        );
    }

    public function categoriesCount(string $key, array $filters = []): int
    {
        $hash = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        $cacheKey = "category {$key} count {$hash}";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['category', $key]
        );

        if ($count === null) {
            $count = $this->dbCategoriesCount($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cachedTime,
                ['category', $key]
            );
        }

        return (int) $count;
    }

    public function lastPageNo(string $key, array $filters = []): int
    {
        $hash = $this->filterHash($filters, ['page']);
        $cacheKey = "category {$key} last page no {$hash}";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['category', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cachedTime,
                ['category', $key]
            );
        }

        return (int) $lastPage;
    }

    public function categories(string $key, array $filters = []): LengthAwarePaginator
    {
        $page = $this->getPage($filters);
        $hash = $this->filterHash($filters, ['page']);
        $cacheKey = "category {$key} page {$page} {$hash}";

        $categories = CacheServerHelper::getCachedData(
            $cacheKey,
            ['category', $key]
        );

        if ($categories === null) {
            $categories = $this->dbCategories($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $categories,
                $this->cachedTime,
                ['category', $key]
            );
        }

        return $categories;
    }

    public function categoryBySlugTree(string $slugTree): Category
    {
        $cacheKey = "category slug tree {$slugTree}";

        $category = CacheServerHelper::getCachedData(
            $cacheKey,
            ['category', 'public']
        );

        if (!$category instanceof Category) {
            $category = Category::where('slug_tree', $slugTree)->firstOrFail();

            CacheServerHelper::cachedData(
                $cacheKey,
                $category,
                $this->cachedTime,
                ['category', 'public']
            );
        }

        return $category;
    }
}
