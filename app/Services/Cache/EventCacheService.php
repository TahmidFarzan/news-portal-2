<?php

namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Helpers\EventHelper;
use App\Models\Event;
use App\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class EventCacheService
{
    private int $perPage = 5000;

    private int $cachedTTL = 86400;

    private string $mainTag = CacheHelper::TAG_EVENT;

    private string $secondKey = CacheHelper::KEY_EVENT;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_PAGE]);
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_SITEMAP]);
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_FEED]);
    }

    private function getPerPage(?int $perPage = null): int
    {
        return $perPage ?? $this->perPage;
    }

    private function generalQueryRecords(Language $language): Builder
    {
        $records = Event::query()->with('language');
        if ($language && $language?->id) {
            $records = $records->orderBy('id', 'asc');
        }

        return $records;
    }

    private function dbLastPageNo(Language $language, ?int $perPage = null): int
    {
        return (int) ceil($this->generalQueryRecords($language)->count() / $this->getPerPage($perPage));
    }

    private function dbRecords(Request $request, Language $language, ?int $perPage = null): LengthAwarePaginator
    {
        $records = Event::query()->with('language');
        if ($language && $language?->id) {
            $records = $records->where('language_id', $language?->id);
        }

        $records = $records->paginate($this->getPerPage($request->input('per_page', $perPage)));

        return $records;
    }

    private function dbRecordsByPosition(Language $language, string $position = EventHelper::POSITION_TOP): Collection
    {
        $records = Event::with([
            'desktopBannerImage',
            'mobileBannerImage',
        ])
            ->where('language_id', $language->id)
            ->where('position', $position)
            ->where('is_active', true)
            ->whereNotNull('start_date')
            ->whereNotNull('end_date')
            ->whereDate('start_date', '<=', now()->toDateString())
            ->whereDate('end_date', '>=', now()->toDateString())
            ->get();

        return $this->appendMediaSrcset($records);
    }

    private function dbRecordByIdOrSlug(Language $language, string|int $idOrSlug): Event
    {
        $record = Event::with([
            'language',
            'desktopBannerImage',
            'mobileBannerImage',
        ])
            ->where('is_active', true);

        if ($language->id) {
            $record->where('language_id', $language->id);
        }

        $record->where(function ($query) use ($idOrSlug) {
            $query->where('slug', $idOrSlug)
                ->orWhere('id', $idOrSlug);
        });

        $event = $record->firstOrFail();

        return $this->appendMediaSrcsetToEvent($event);
    }

    private function appendMediaSrcset(Collection $events): Collection
    {
        $events->each(function (Event $event) {
            $this->appendMediaSrcsetToEvent($event);
        });

        return $events;
    }

    private function appendMediaSrcsetToEvent(Event $event): Event
    {
        foreach (['desktopBannerImage', 'mobileBannerImage'] as $relation) {
            $media = $event->{$relation};

            if ($media) {
                $media->setAttribute(
                    'srcset',
                    $media->getSrcset()
                );
            }
        }

        return $event;
    }

    public function getLastPageNo(string $key, Language $language, ?int $perPage = null, ?int $cachedTTL = null): int
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForLastPageNo($key, $this->secondKey, $language);

        $lastPage = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $key]
        );

        if ($lastPage === null) {
            $lastPage = $this->dbLastPageNo($language, $perPage);

            CacheServerHelper::cachedData(
                $cacheKey,
                $lastPage,
                $cachedTTL ?? $this->cachedTTL,
                [$this->mainTag, $key]
            );
        }

        return (int) $lastPage;
    }

    public function getRecords(string $key, Request $request, Language $language, ?int $cachedTTL = null): LengthAwarePaginator
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForRecordsRequest($key, $this->secondKey, $request, $language);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $key]
        );

        if ($records === null) {
            $records = $this->dbRecords($request, $language);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [$this->mainTag, $key]
            );
        }

        return $records;
    }

    public function getRecordsByPosition(string $key, Language $language, string $position = EventHelper::POSITION_TOP,  ?int $cachedTTL = null): Collection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForEventByPosition($key, $this->secondKey, $position, $language);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $key]
        );

        if ($records === null) {
            $records = $this->dbRecordsByPosition($language, $position);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [$this->mainTag, $key]
            );
        }

        return $records;
    }

    public function getRecordById(string $key, int|string $id, Language $language, ?int $cachedTTL = null): Event
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordBySlug($key, $this->secondKey, $id, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByIdOrSlug($language, $id,);

            CacheServerHelper::cachedData(
                $cacheKey,
                $record,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $key,
                    $this->mainTag,
                ]
            );
        }

        return $record;
    }

    public function getRecordBySlug(string $key, Language $language, string $slug,  ?int $cachedTTL = null): Event
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordBySlug($key, $this->secondKey, $slug, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByIdOrSlug($language, $slug,);

            CacheServerHelper::cachedData(
                $cacheKey,
                $record,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $key,
                    $this->mainTag,
                ]
            );
        }

        return $record;
    }
}
