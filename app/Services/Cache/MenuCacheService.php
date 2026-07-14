<?php
namespace App\Services\Cache;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;

class MenuCacheService
{
    private int $cachedTTL = 300;
    private int $perPage   = 10;

    private string $mainTag   = CacheHelper::TAG_MENU;
    private string $secondKey = CacheHelper::KEY_MENU;

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

    private function dbMenuBySlug(string $slug, ?Language $language = null): Menu
    {
        $record = Menu::with([
            'menuType',
            'language',
            'menuItems',
        ]);

        if ($language && $language?->id) {
            $record = $record->where("language_id", $language->id);
        }
        $record = $record->where('slug', $slug)->firstOrFail();

        return $record;
    }

    private function dbMenuByMenuTypeCode(string $menuTypeCode, ?Language $language = null): Menu
    {
        $record = Menu::with([
            'menuType',
            'language',
            'menuItems',
        ]);

        if ($language && $language?->id) {
            $record = $record->where("language_id", $language->id);
        }
        $record = $record->whereRelation('menuType', 'name', $menuTypeCode)->firstOrFail();

        return $record;
    }

    private function dbMenuItems(Menu $menu, MenuItem | null $parentMenuItem = null, ?Language $language = null, int | null $perPage = null)
    {
        $records = MenuItem::with([
            'model',
            'language',
            'menu',
            'menu.menuType',
            'menu.language',
        ]);

        if ($parentMenuItem && $parentMenuItem?->id) {
            $records = $records->where("parent_id", $parentMenuItem?->id);
        } else {
            $records = $records->whereNull("parent_id");
        }

        if ($menu && $menu?->id) {
            $records = $records->where('menu_id', $menu->id);
        }

        if ($language && $language?->id) {
            $records = $records->where("language_id", $language->id);
        }

        $records = $records->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($this->getPerPage($perPage));

        return $records;
    }

    private function dbMenuItemBySlug(string $slug, ?Language $language = null): MenuItem
    {
        $record = MenuItem::with([
            'model',
            'language',
            'menu',
            'menu.menuType',
            'menu.language',
        ]);
        if ($language && $language?->id) {
            $record = $record->where("language_id", $language->id);
        }
        $record = $record->where('slug', $slug)->firstOrFail();

        return $record;
    }

    public function getMenuBySlug(string $key, string $slug, ?Language $language = null, int | null $cachedTTL = null): Menu
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
            $record = $this->dbMenuBySlug($slug, $language);

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

    public function getMenuByMenuTypeCode(string $key, string $menuTypeCode, ?Language $language = null, int | null $cachedTTL = null): Menu
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
            $record = $this->dbMenuByMenuTypeCode($menuTypeCode, $language);

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

    public function getMenuItemBySlug(string $key, string $slug, ?Language $language = null, int | null $cachedTTL = null): MenuItem
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
            $record = $this->dbMenuItemBySlug($slug, $language);

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

    public function getMenuItems(string $key, Menu $menu, MenuItem | null $parentMenuItem = null, ?Language $language = null, int | null $perPage = null, int | null $cachedTTL = null)
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
            $records = $this->dbMenuItems($menu, $parentMenuItem, $language, $perPage);

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
