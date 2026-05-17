<?php

namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class TagCacheService
{
    private int $cachedTime = 86400;
    private int $perPage = 5000;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag(['tag', 'public']);
        CacheServerHelper::clearCachedByTag(['tag', 'sitemap']);
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

    private function queryTags(array $filters = []): Builder
    {
        return Tag::query()
            ->with('language')
            ->orderBy('id', 'asc');
    }

    public function dbTagsCount(array $filters = []): int
    {
        return $this->queryTags($filters)->count();
    }

    public function dbLastPageNo(array $filters = []): int
    {
        return (int) ceil($this->dbTagsCount($filters) / $this->getPerPage($filters));
    }

    private function dbTags(array $filters = []): LengthAwarePaginator
    {
        return $this->queryTags($filters)->paginate(
            $this->getPerPage($filters),
            ['*'],
            'page',
            $this->getPage($filters)
        );
    }

    public function cachedTags(string $key, array $filters = []): void
    {
        $page = $this->getPage($filters);
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "tag {$key} page {$page} {$hash}",
            $this->dbTags($filters),
            $this->cachedTime,
            ['tag', $key]
        );
    }

    public function cachedTagsCount(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        CacheServerHelper::cachedData(
            "tag {$key} count {$hash}",
            $this->dbTagsCount($filters),
            $this->cachedTime,
            ['tag', $key]
        );
    }

    public function cachedLastPageNo(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "tag {$key} last page no {$hash}",
            $this->dbLastPageNo($filters),
            $this->cachedTime,
            ['tag', $key]
        );
    }

    public function tagsCount(string $key, array $filters = []): int
    {
        $hash = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        $cacheKey = "tag {$key} count {$hash}";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['tag', $key]
        );

        if ($count === null) {
            $count = $this->dbTagsCount($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cachedTime,
                ['tag', $key]
            );
        }

        return (int) $count;
    }

    public function lastPageNo(string $key, array $filters = []): int
    {
        $hash = $this->filterHash($filters, ['page']);
        $cacheKey = "tag {$key} last page no {$hash}";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['tag', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cachedTime,
                ['tag', $key]
            );
        }

        return (int) $lastPage;
    }

    public function tags(string $key, array $filters = []): LengthAwarePaginator
    {
        $page = $this->getPage($filters);
        $hash = $this->filterHash($filters, ['page']);
        $cacheKey = "tag {$key} page {$page} {$hash}";

        $tags = CacheServerHelper::getCachedData(
            $cacheKey,
            ['tag', $key]
        );

        if ($tags === null) {
            $tags = $this->dbTags($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $tags,
                $this->cachedTime,
                ['tag', $key]
            );
        }

        return $tags;
    }

    public function tag(string $slug): Tag
    {
        $cacheKey = "tag slug tree {$slug}";

        $tag = CacheServerHelper::getCachedData(
            $cacheKey,
            ['tag', 'public']
        );

        if (!$tag instanceof Tag) {
            $tag = Tag::where('slug', $slug)->firstOrFail();

            CacheServerHelper::cachedData(
                $cacheKey,
                $tag,
                $this->cachedTime,
                ['tag', 'public']
            );
        }

        return $tag;
    }
}
