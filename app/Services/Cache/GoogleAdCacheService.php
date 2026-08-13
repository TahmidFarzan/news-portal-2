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

    private function dbGoogleAdsByTypeAndPlacement(string $page, string $type, string|int|null $placement): Collection
    {
        $googleAds = GoogleAd::where('page', $page)->where('type', $type);
        if ($placement) {
            $googleAds = $googleAds->where('placement', $placement);
        }
        $googleAds = $googleAds->orderBy('id', 'desc')->get();
        return $googleAds;
    }

    public function getGoogleAdsByTypeAndPlacement(string $key, string $page, string $type, string|int|null $placement, ?int $cachedTTL = null): Collection
    {
        $cacheKey = CacheHelper::cacheKeyGenerateGoogleAdsByTypeAndPlacement($key, $this->secondKey, $page, $type, $placement);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $records) {
            $records = $this->dbGoogleAdsByTypeAndPlacement($page, $type, $placement);

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
