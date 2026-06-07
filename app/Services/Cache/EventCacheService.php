<?php

namespace App\Services\Cache;

use App\Helpers\CacheServerHelper;
use App\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

class EventCacheService
{
    private int $cachedTime = 86400;
    private int $perPage = 5000;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag(['event', 'public']);
        CacheServerHelper::clearCachedByTag(['event', 'sitemap']);
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

    private function queryEvents(array $filters = []): Builder
    {
        return Event::query()
            ->with('language')
            ->orderBy('id', 'asc');
    }

    public function dbEventsCount(array $filters = []): int
    {
        return $this->queryEvents($filters)->count();
    }

    public function dbLastPageNo(array $filters = []): int
    {
        return (int) ceil($this->dbEventsCount($filters) / $this->getPerPage($filters));
    }

    private function dbEvents(array $filters = []): LengthAwarePaginator
    {
        return $this->queryEvents($filters)->paginate(
            $this->getPerPage($filters),
            ['*'],
            'page',
            $this->getPage($filters)
        );
    }

    public function cachedEvents(string $key, array $filters = []): void
    {
        $page = $this->getPage($filters);
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "event:{$key}:page:{$page}:{$hash}",
            $this->dbEvents($filters),
            $this->cachedTime,
            ['event', $key]
        );
    }

    public function cachedEventsCount(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        CacheServerHelper::cachedData(
            "event:{$key}:count:{$hash}",
            $this->dbEventsCount($filters),
            $this->cachedTime,
            ['event', $key]
        );
    }

    public function cachedLastPageNo(string $key, array $filters = []): void
    {
        $hash = $this->filterHash($filters, ['page']);

        CacheServerHelper::cachedData(
            "event:{$key}:last:page-no:{$hash}",
            $this->dbLastPageNo($filters),
            $this->cachedTime,
            ['event', $key]
        );
    }

    public function eventsCount(string $key, array $filters = []): int
    {
        $hash = $this->filterHash($filters, ['page', 'per_page', 'perPage']);
        $cacheKey = "event:{$key}:count:{$hash}";

        $count = CacheServerHelper::getCachedData(
            $cacheKey,
            ['event', $key]
        );

        if ($count === null) {
            $count = $this->dbEventsCount($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $count,
                $this->cachedTime,
                ['event', $key]
            );
        }

        return (int) $count;
    }

    public function lastPageNo(string $key, array $filters = []): int
    {
        $hash = $this->filterHash($filters, ['page']);
        $cacheKey = "event:{$key}:last-page-no:{$hash}";

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            ['event', $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $this->cachedTime,
                ['event', $key]
            );
        }

        return (int) $lastPage;
    }

    public function events(string $key, array $filters = []): LengthAwarePaginator
    {
        $page = $this->getPage($filters);
        $hash = $this->filterHash($filters, ['page']);
        $cacheKey = "event:{$key}:page:{$page}:{$hash}";

        $events = CacheServerHelper::getCachedData(
            $cacheKey,
            ['event', $key]
        );

        if ($events === null) {
            $events = $this->dbEvents($filters);

            CacheServerHelper::cachedData(
                $cacheKey,
                $events,
                $this->cachedTime,
                ['event', $key]
            );
        }

        return $events;
    }

    public function event(string $slug): Event
    {
        $cacheKey = "event:slug:{$slug}";

        $event = CacheServerHelper::getCachedData(
            $cacheKey,
            ['event', 'slug']
        );

        if (!$event instanceof Event) {
            $event = Event::where('slug', $slug)->firstOrFail();

            CacheServerHelper::cachedData(
                $cacheKey,
                $event,
                $this->cachedTime,
                ['event', 'slug']
            );
        }

        return $event;
    }
}
