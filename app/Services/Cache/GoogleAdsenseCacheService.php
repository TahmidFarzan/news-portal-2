<?php

namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\GoogleAdsense;
use Illuminate\Support\Collection;

class GoogleAdsenseCacheService
{
    private int $cachedTTL = 86400;

    private string $mainTag = CacheHelper::TAG_GOOGLE_ADSENSE;

    private string $secondKey = CacheHelper::KEY_GOOGLE_ADSENSE;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_PAGE]);
    }

    private function dbGoogleAdsensesByTypeAndPosition(string $type, string $position): Collection
    {
        return GoogleAdsense::where('type', $type)->where('position', $position)->orderBy('position', 'asc')->get();
    }

    public function getGoogleAdsensesByTypeAndPosition(string $key, string $type, string $position, ?int $cachedTTL = null): Collection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateGoogleAdsensesByTypeAndPosition($key, $this->secondKey, $type, $position);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $records) {
            $records = $this->dbGoogleAdsensesByTypeAndPosition($type, $position);

            CacheServerHelper::cachedData(
                $cacheKey,
                $records,
                $cachedTTL ?? $this->cachedTTL,
                [
                    $key,
                    $this->mainTag,
                ]
            );
        }

        return $records;
    }
}
