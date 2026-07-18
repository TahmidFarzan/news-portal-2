<?php
namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Language;
use App\Models\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PageCacheService
{
    private int $perPage   = 5000;
    private int $cachedTTL = 86400;

    private string $mainTag   = CacheHelper::TAG_PAGE;
    private string $secondKey = CacheHelper::KEY_PAGE;

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

    private function getPerPage(int | null $perPage = null): int
    {
        return $perPage ?? $this->perPage;
    }

    private function generalQueryRecords(Language $language): Builder
    {
        $records = Page::query()
            ->with(['language'])
            ->where('is_published', true)
            ->orderBy('id', 'asc');

        if ($language && $language?->id) {
            $records = $records->where("language_id", $language?->id);
        }
        return $records;
    }

    private function dbLastPageNo(Language $language, int | null $perPage = null): int
    {
        return (int) ceil($this->generalQueryRecords($language)->count() / $this->getPerPage($perPage));
    }

    private function dbRecords(Request $request, Language $language, int | null $perPage = null): LengthAwarePaginator
    {
        $records = Page::query()
            ->with(['language'])
            ->where('is_published', true)
            ->orderBy('id', 'asc');

        if ($language && $language?->id) {
            $records = $records->where("language_id", $language?->id);
        }

        $records = $records->paginate($this->getPerPage($request->input("per_page", $perPage)));

        return $records;
    }

    private function dbRecordByIdOrSlug(Language $language, string | int $idOrSlug): Page
    {
        $record = Page::with(['language'])->where('is_published', true)->where('is_default', false);

        if ($language && $language?->id) {
            $record = $record->where("language_id", $language?->id);
        }
        $record = $record->where('slug', $idOrSlug)
            ->orWhere('id', $idOrSlug)
            ->firstOrFail();
        return $record;
    }

    private function dbRecordSlugTree(Language $language, string $slugTree): Page
    {
        $record = Page::with(['language'])->where('is_published', true)->where('is_default', false);

        if ($language && $language?->id) {
            $record = $record->where("language_id", $language?->id);
        }
        $record = $record->where('slug_tree', $slugTree)
            ->firstOrFail();
        return $record;
    }

    private function dbRecordUseAs(Language $language, string $useAs): Page
    {
        $record = Page::with(['language'])->where('is_published', true)->where('is_default', true);

        if ($language && $language?->id) {
            $record = $record->where("language_id", $language?->id);
        }
        $record = $record->where('default_use_as', $useAs)
            ->firstOrFail();
        return $record;
    }

    public function getLastPageNo(string $key, Language $language, int | null $perPage = null, int | null $cachedTTL = null): int
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

    public function getRecords(string $key, Request $request, Language $language, int | null $cachedTTL = null): LengthAwarePaginator
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

    public function getRecordBySlugTree(string $key, Language $language,string $slugTree,  int | null $cachedTTL = null): Page
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
            $record = $this->dbRecordSlugTree($language, $slugTree, );

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

    public function getRecordById(string $key, Language $language,int | string $id,  int | null $cachedTTL = null): Page
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
            $record = $this->dbRecordByIdOrSlug($language,$id, );

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

    public function getRecordBySlug(string $key, Language $language,string $slug,  int | null $cachedTTL = null): Page
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
            $record = $this->dbRecordByIdOrSlug($language, $slug, );

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

    public function getRecordByUseAs(string $key, Language $language, string $useAs,  int | null $cachedTTL = null): Page
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordByUseAs($key, $this->secondKey, $useAs, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordUseAs($language, $useAs, );

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
