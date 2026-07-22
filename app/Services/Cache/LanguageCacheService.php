<?php
namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Language;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class LanguageCacheService
{
    private int $perPage   = 5000;
    private int $cachedTTL = 86400;

    private string $mainTag   = CacheHelper::TAG_LANGUAGE;
    private string $secondKey = CacheHelper::KEY_LANGUAGE;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_PAGE]);
    }

    private function getPerPage(int | null $perPage = null): int
    {
        return $perPage ?? $this->perPage;
    }

    private function dbRecords(Request $request, int | null $perPage = null): LengthAwarePaginator
    {
        return Language::query()->orderBy('id', 'asc')->paginate($this->getPerPage($request->input("per_page", $perPage)));;
    }

    private function dbRecordByIdOrSlug(string | int $idOrSlug): Language
    {
        return Language::where('id', $idOrSlug)->orWhere('slug', $idOrSlug)->firstOrFail();
    }

    private function dbRecordByCodeFirst(string $code): Language|null
    {
        return Language::where('code', $code)->first() ?? null;
    }

    private function dbRecordByDefault(): Language
    {
        return Language::where('is_default', true)->firstOrFail();
    }

    public function getRecords(string $key, Request $request, int | null $cachedTTL = null): LengthAwarePaginator
    {
        $cacheKey = CacheHelper::cacheKeyGenerateForRecordsRequestWithoutLanguage($key, $this->secondKey, $request);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [$this->mainTag, $key]
        );

        if ($records === null) {
            $records = $this->dbRecords($request);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [$this->mainTag, $key]
            );
        }

        return $records;
    }

    public function getRecordById(string $key, int | string $id, int | null $cachedTTL = null): Language
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordBySlugWithoutLanguage($key, $this->secondKey, $id);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByIdOrSlug($id);

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

    public function getRecordBySlug(string $key, string $slug, int | null $cachedTTL = null): Language
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordBySlugWithoutLanguage($key, $this->secondKey, $slug);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByIdOrSlug($slug);

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

    public function getRecordByCodeFirst(string $key, string $code, int | null $cachedTTL = null): Language | null
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordByCodeWithoutLanguage($key, $this->secondKey, $code);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByCodeFirst($code);

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

    public function getRecordByDefault(string $key, int | null $cachedTTL = null): Language
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleDefaultRecordByCodeWithoutLanguage($key, $this->secondKey);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbRecordByDefault();

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
