<?php

namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\GoogleAd;
use Illuminate\Support\Collection;

class GoogleAdCacheService
{
    private int $cachedTTL = 86400;

    private string $mainTag = CacheHelper::TAG_GOOGLE_AD;

    private string $secondKey = CacheHelper::KEY_GOOGLE_AD;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_PAGE]);
    }

    private function dbGoogleAdsByTypeAndPosition(string $type, string $position): Collection
    {
        return GoogleAd::where('type', $type)->where('position', $position)->orderBy('position', 'asc')->get();
    }

    public function getGoogleAdsByTypeAndPosition(string $key, string $type, string $position, ?int $cachedTTL = null): Collection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateGoogleAdsByTypeAndPosition($key, $this->secondKey, $type, $position);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $records) {
            $records = $this->dbGoogleAdsByTypeAndPosition($type, $position);

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
