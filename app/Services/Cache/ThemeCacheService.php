<?php
namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Theme;

class ThemeCacheService
{
    private int $cachedTTL = 300;

    private string $mainTag   = CacheHelper::TAG_THEME;
    private string $secondKey = CacheHelper::KEY_THEME;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_PAGE]);
    }

    private function dbThemes()
    {
        return Theme::orderBy('position', 'asc')->orderBy('id', 'asc')->get();
    }

    private function dbThemesByGroupAndLabels(string $group, array $labels)
    {
        return Theme::orderBy('position', 'asc')->where('group', $group)->whereIn('label', $labels)->orderBy('id', 'asc')->get();
    }

    private function dbThemeByGroupAndLabel(string $group, string $label)
    {
        return Theme::where('group', $group)->where('label', $label)->orderBy('id', 'desc')->firstOrFail();
    }

    public function getThemes(string $key, int | null $cachedTTL = null)
    {
        $cacheKey = CacheHelper::cacheKeyGenerateThemes($key, $this->secondKey);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $records) {
            $records = $this->dbThemes();

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

    public function getThemesByGroupAndLabels(string $key, string $group, array $labels, int | null $cachedTTL = null)
    {
        $cacheKey = CacheHelper::cacheKeyGenerateThemesByGroupAndLabels($key, $this->secondKey, $group, $labels);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $records) {
            $records = $this->dbThemesByGroupAndLabels($group, $labels);

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

    public function getThemeByGroupAndLabel(string $key, string $group, string $label, int | null $cachedTTL = null)
    {
        $cacheKey = CacheHelper::cacheKeyGenerateThemesByGroupAndLabel($key, $this->secondKey, $group, $label);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbThemeByGroupAndLabel($group, $label);

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
