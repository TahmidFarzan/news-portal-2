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

    public function dbCategoriesCount()
    {
        return Category::count();
    }

    public function dbLastPageNo($perPage = null)
    {
        $perPage = $perPage ?? $this->perPage;
        return (int) ceil($this->dbCategoriesCount() / $perPage);
    }

    private function dbCategories($perPage = null, $page = 1)
    {
        $perPage = $perPage ?? $this->perPage;

        return Category::orderBy('id', 'asc')->with("language")->paginate($perPage, ['*'], 'page', $page);
    }

    /* -------------------------------------------------
    | CACHE WRITE
    |-------------------------------------------------*/

    public function cachedCategories($key, $perPage = null, $page = 1)
    {
        CacheServerHelper::cachedData(
            "category {$key} page {$page}",
            $this->dbCategories($perPage, $page),
            $this->cahedTime,
            ['category', $key]
        );
    }

    public function cachedCategoriesCount($key)
    {
        CacheServerHelper::cachedData(
            "category {$key} count",
            $this->dbCategoriesCount(),
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

    public function categoriesCount($key)
    {
        $cacheKey = "category {$key} count";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['category', $key]
        );

        if ($count === null) {
            $count = $this->dbCategoriesCount();
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

    public function categories($key, $perPage = null, $page = 1)
    {
        $cacheKey = "category {$key} page {$page}";

        $categories = CacheServerHelper::getCachedData(
            $cacheKey,
            ['category', $key]
        );

        if ($categories === null) {
            $categories = $this->dbCategories($perPage, $page);
            CacheServerHelper::cachedData(
                $cacheKey,
                $categories,
                $this->cahedTime,
                ['category', $key]
            );
        }

        return $categories;
    }
}
