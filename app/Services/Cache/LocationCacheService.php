<?php

namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Category;
use App\Models\Language;
use App\Models\Location;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LocationCacheService
{
    private int $perPage = 5000;

    private int $cachedTTL = 86400;

    private string $mainTag = CacheHelper::TAG_LOCATION;

    private string $secondKey = CacheHelper::KEY_LOCATION;

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
        $records = Location::query()->with('language');
        if ($language && $language?->id) {
            $records = $records->orderBy('id', 'asc');
        }

        return $records;
    }

    private function dbRecordMaxDepthAndLevel(Language $language, ?Category $category,): object
    {

        $maxDepth = 0;
        if (DB::connection()->getDriverName() === 'sqlite') {

            $bindings = [
                $category?->id,
                $language->id,
                $category?->id,
                $language->id,
            ];

            $row = DB::selectOne(
                <<<SQL
            WITH RECURSIVE location_tree AS (
                SELECT
                    id,
                    parent_id,
                    0 AS depth
                FROM locations
                WHERE parent_id IS NULL
                  AND category_id = ?
                  AND language_id = ?

                UNION ALL

                SELECT
                    l.id,
                    l.parent_id,
                    lt.depth + 1
                FROM locations l
                INNER JOIN location_tree lt
                    ON lt.id = l.parent_id
                WHERE l.category_id = ?
                  AND l.language_id = ?
            )
            SELECT COALESCE(MAX(depth), 0) AS max_depth
            FROM location_tree
            SQL,
                $bindings
            );

            $maxDepth = (int) ($row->max_depth ?? 0);
        } else {

            $maxDepth = Location::withQueryConstraint(
                fn(Builder $query) => $query
                    ->where('locations.category_id', $category?->id)
                    ->where('locations.language_id', $language->id),

                fn() => Location::treeOf(
                    fn(Builder $query) => $query
                        ->whereNull('parent_id')
                )
            )->max('depth') ?? 0;
        }

        $maxDepth = $maxDepth !== null ? (int) $maxDepth : null;

        $data = (object) [
            'max_depth' => $maxDepth ?? 0,
            'max_level' => $maxDepth !== null ? $maxDepth + 1 : 0,
        ];

        return $data;
    }

    private function dbLastPageNo(Language $language, ?int $perPage = null): int
    {
        return (int) ceil($this->generalQueryRecords($language)->count() / $this->getPerPage($perPage));
    }

    private function dbRecords(Request $request, Language $language, ?int $perPage = null): LengthAwarePaginator
    {
        $records = Location::query()->with('language');
        if ($language && $language?->id) {
            $records = $records->where('language_id', $language?->id);
        }

        $records = $records->paginate($this->getPerPage($request->input('per_page', $perPage)));

        return $records;
    }

    private function dbRecordByIdOrSlug(Language $language, string | int $idOrSlug,): Location
    {
        $record = Location::with(['language', 'parent', 'children']);

        if ($language && $language?->id) {
            $record = $record->where('language_id', $language?->id);
        }

        $record = $record->where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->firstOrFail();

        return $record;
    }

    private function dbRecordSlugTree(Language $language, string $slugTree,): Location
    {
        $record = Location::with(['language', 'parent', 'children']);

        if ($language && $language?->id) {
            $record = $record->where('language_id', $language?->id);
        }

        $record = $record->where('slug_tree', $slugTree)
            ->firstOrFail();

        return $record;
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

    public function getMaxDepthAndLevel(string $key, Language $language, ?Category $category, ?int $cachedTTL = null): object
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForMaxDepthAndLevel($key, $this->secondKey, $category, $language);

        $data = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $data) {
            $data = $this->dbRecordMaxDepthAndLevel($language, $category,);

            CacheServerHelper::cachedData(
                $cacheKey,
                $data,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $key,
                    $this->mainTag,
                ]
            );
        }

        return $data;
    }

    public function getRecordBySlugTree(string $key, Language $language, string $slugTree, ?int $cachedTTL = null): Location
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordBySlugTree($key, $this->secondKey, $slugTree, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordSlugTree($language, $slugTree,);

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

    public function getRecordById(string $key, Language $language, int | string $id, ?int $cachedTTL = null): Location
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

    public function getRecordBySlug(string $key, Language $language, string $slug, ?int $cachedTTL = null): Location
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
