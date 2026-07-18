<?php
namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use Illuminate\Pagination\LengthAwarePaginator;

class MenuCacheService
{
    private int $cachedTTL = 300;

    private int $perPage = 10;

    private string $mainTag = CacheHelper::TAG_MENU;

    private string $secondKey = CacheHelper::KEY_MENU;

    public function isConnected(): bool
    {
        return CacheServerHelper::isConnected();
    }

    public function clearCached(): void
    {
        CacheServerHelper::clearCachedByTag([$this->mainTag, CacheHelper::TAG_PAGE]);
    }

    private function getPerPage(?int $perPage = null): int
    {
        return $perPage ?? $this->perPage;
    }

    private function dbMenuBySlug(Language $language, string $slug): Menu
    {
        $record = Menu::with([
            'menuType',
            'language',
            'menuItems',
        ]);

        if ($language && $language?->id) {
            $record = $record->where('language_id', $language->id);
        }
        $record = $record->where('slug', $slug)->firstOrFail();

        return $record;
    }

    private function dbMenuByMenuTypeCode(Language $language, string $menuTypeCode, ): Menu
    {
        $record = Menu::with([
            'menuType',
            'language',
            'menuItems',
        ]);

        if ($language && $language?->id) {
            $record = $record->where('language_id', $language->id);
        }
        $record = $record->whereRelation('menuType', 'name', $menuTypeCode)->firstOrFail();

        return $record;
    }

    private function dbMenuItems(Language $language, Menu $menu, ?MenuItem $parentMenuItem = null, ?int $perPage = null): LengthAwarePaginator
    {
        $records = MenuItem::with([
            'model',
            'language',
            'menu',
            'menu.menuType',
            'menu.language',
        ]);

        if ($parentMenuItem && $parentMenuItem?->id) {
            $records = $records->where('parent_id', $parentMenuItem?->id);
        } else {
            $records = $records->whereNull('parent_id');
        }

        if ($menu && $menu?->id) {
            $records = $records->where('menu_id', $menu->id);
        }

        if ($language && $language?->id) {
            $records = $records->where('language_id', $language->id);
        }

        $records = $records->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($this->getPerPage($perPage));

        return $records;
    }

    private function dbMenuItemBySlug(Language $language, string $slug): MenuItem
    {
        $record = MenuItem::with([
            'model',
            'language',
            'menu',
            'menu.menuType',
            'menu.language',
        ]);
        if ($language && $language?->id) {
            $record = $record->where('language_id', $language->id);
        }
        $record = $record->where('slug', $slug)->firstOrFail();

        return $record;
    }

    public function getMenuBySlug(string $key, Language $language, string $slug, ?int $cachedTTL = null): Menu
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
            $record = $this->dbMenuBySlug($language, $slug);

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

    public function getMenuByMenuTypeCode(string $key, Language $language, string $menuTypeCode, ?int $cachedTTL = null): Menu
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleRecordBySlug($key, $this->secondKey, $menuTypeCode, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbMenuByMenuTypeCode($language, $menuTypeCode);

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

    public function getMenuItemBySlug(string $key, Language $language, string $slug, ?int $cachedTTL = null): MenuItem
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleMenuItemBySlug($key, $this->secondKey, $slug, $language);

        $record = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $record) {
            $record = $this->dbMenuItemBySlug($language, $slug);

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

    public function getMenuItems(string $key, Language $language, Menu $menu, ?MenuItem $parentMenuItem = null, ?int $perPage = null, ?int $cachedTTL = null): LengthAwarePaginator
    {
        $cacheKey = CacheHelper::cacheKeyGenerateSingleMenuItems($key, $this->secondKey, $menu, $parentMenuItem, $language);

        $records = CacheServerHelper::getCachedData(
            $cacheKey,
            [
                $key,
                $this->mainTag,
            ]
        );

        if (! $records) {
            $records = $this->dbMenuItems($language, $menu, $parentMenuItem, $perPage);

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
