<?php
namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class PageCacheService
{
    private int $cachedTime = 86400;
    private int $perPage    = 5000;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag(['page', 'public']);
        CacheServerHelper::clearCachedByTag(['page', 'sitemap']);
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

    private function queryPages(array $filters = []): Builder
    {
        return Page::query()
            ->with('language')
            ->where("is_published", true)->orderBy('id', 'asc');
    }

    public function dbPagesCount(array $filters = []): int
    {
        return $this->queryPages($filters)->count();
    }

    public function dbLastPageNo(array $filters = []): int
    {
        return (int) ceil($this->dbPagesCount($filters) / $this->getPerPage($filters));
    }

    private function dbPages(array $filters = []): LengthAwarePaginator
    {
        return $this->queryPages($filters)->paginate(
            $this->getPerPage($filters),
            ['*'],
            'page',
            $this->getPage($filters)
        );
    }

    public function cachedPages(string $key, array $filters = []): void
    {
        $page = $this->getPage($filters);
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "page:{$key}:page:{$page}:{$hash}",
            $this->dbPages($filters),
            $this->cachedTime,
            ['page', $key]
        );
    }

    public function cachedPagesCount(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        CacheServerHelper::cachedData(
            "page:{$key}:count:{$hash}",
            $this->dbPagesCount($filters),
            $this->cachedTime,
            ['page', $key]
        );
    }

    public function cachedLastPageNo(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "page:{$key}:last:page-no:{$hash}",
            $this->dbLastPageNo($filters),
            $this->cachedTime,
            ['page', $key]
        );
    }

    public function pagesCount(string $key, array $filters = []): int
    {
        $hash     = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        $cacheKey = "page:{$key}:count:{$hash}";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['page', $key]
        );

        if ($count === null) {
            $count = $this->dbPagesCount($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cachedTime,
                ['page', $key]
            );
        }

        return (int) $count;
    }

    public function lastPageNo(string $key, array $filters = []): int
    {
        $hash     = $this->filterHash($filters, ['page']);
        $cacheKey = "page:{$key}:last-page-no:{$hash}";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['page', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cachedTime,
                ['page', $key]
            );
        }

        return (int) $lastPage;
    }

    public function pages(string $key, array $filters = []): LengthAwarePaginator
    {
        $page     = $this->getPage($filters);
        $hash     = $this->filterHash($filters, ['page']);
        $cacheKey = "page:{$key}:page:{$page}:{$hash}";

        $pages = CacheServerHelper::getCachedData(
            $cacheKey,
            ['page', $key]
        );

        if ($pages === null) {
            $pages = $this->dbPages($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $pages,
                $this->cachedTime,
                ['page', $key]
            );
        }

        return $pages;
    }

    public function page(string $slug): Page
    {
        $cacheKey = "page:slug:{$slug}";

        $page = CacheServerHelper::getCachedData(
            $cacheKey,
            ['page', 'slug']
        );

        if (! $page instanceof Page) {
            $page = Page::where('slug', $slug)->firstOrFail();

            CacheServerHelper::cachedData(
                $cacheKey,
                $page,
                $this->cachedTime,
                ['page', 'slug']
            );
        }

        return $page;
    }
}
