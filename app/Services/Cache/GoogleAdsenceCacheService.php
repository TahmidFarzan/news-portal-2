<?php
namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\GoogleAdsence;

class GoogleAdsenceCacheService
{
    private int $cachedTTL = 86400;

    private string $mainTag   = CacheHelper::TAG_GOOGLE_ADSENCE;
    private string $secondKey = CacheHelper::KEY_GOOGLE_ADSENCE;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_PAGE]);
    }

    private function dbGoogleAdsencesByTypeAndPosition(string $type, string $position)
    {
        return GoogleAdsence::where('type', $type)->where('position', $position)->orderBy('position', 'asc')->get();
    }

    public function getGoogleAdsencesByTypeAndPosition(string $key, string $type, string $position, int | null $cachedTTL = null)
    {
        $cacheKey = CacheHelper::cacheKeyGenerateGoogleAdsencesByTypeAndPosition($key, $this->secondKey, $type, $position);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $records) {
            $records = $this->dbGoogleAdsencesByTypeAndPosition($type, $position);

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
