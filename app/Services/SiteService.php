<?php
namespace App\Services;

use App\Helpers\CacheServerHelper;
//use App\Models\Language;
use App\Helpers\MenuHelper;
use App\Helpers\SystemHelper;
use App\Models\BreakingNews;
use App\Models\MenuItem;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteService
{
    public function menuItem(string $slug): MenuItem
    {
        return MenuItem::where('slug', $slug)->firstOrFail();
    }

    public function menuItemRelationLoad(MenuItem $menuItem): MenuItem
    {
        $menuItem->load([
            "parent",

            'model',
            'language',
        ]);

        return $menuItem;
    }

    public function menuHeaderMenuMenuItems(Request $request): array
    {
        $perPage = 10;
        $page    = max((int) $request->input('page', 1), 1);

        $languageCode   = SystemHelper::LANGUAGE_DEFAULT_CODE;
        $headerMenuCode = MenuHelper::MENU_TYPE_HEADER;

        $cacheKey = "site header navbar {$languageCode} {$headerMenuCode} page {$page} per page {$perPage}";

        $cacheTags = [
            'site',
            'site-header',
            'site-header-navbar',
            'site-header-navbar-menu-items',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $query = MenuItem::query()
            ->with([
                'menu.language',
                'menu.menuType',
                'children',
                'model',
            ])
            ->whereNull("parent_id")
            ->whereRelation('menu.language', 'code', $languageCode)
            ->whereRelation('menu.menuType', 'name', $headerMenuCode)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        $list = $query->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
            'public_url'      => $row->public_url,
        ]);

        $data = [
            'items'        => $list,
            'total'        => $query->total(),
            'current_page' => $query->currentPage(),
            'last_page'    => $query->lastPage(),
            'per_page'     => $query->perPage(),
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function menuOffCanvasMenuMenuItems(Request $request): array
    {
        $perPage = 20;
        $page    = max((int) $request->input('page', 1), 1);

        $languageCode      = SystemHelper::LANGUAGE_DEFAULT_CODE;
        $offcanvasMenuCode = MenuHelper::MENU_TYPE_OFFCANVAS;

        $cacheKey = "site offcanvas {$languageCode} {$offcanvasMenuCode} page {$page} per page {$perPage}";

        $cacheTags = [
            'site',
            'site-offcanvas',
            'site-offcanvas-menu',
            'site-offcanvas-menu-items',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $query = MenuItem::query()
            ->with([
                'menu.language',
                'menu.menuType',
                'children',
                'model',
            ])
            ->whereNull("parent_id")
            ->whereRelation('menu.language', 'code', $languageCode)
            ->whereRelation('menu.menuType', 'name', $offcanvasMenuCode)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        $list = $query->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
            'public_url'      => $row->public_url,
        ]);

        $data = [
            'items'        => $list,
            'total'        => $query->total(),
            'current_page' => $query->currentPage(),
            'last_page'    => $query->lastPage(),
            'per_page'     => $query->perPage(),
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function menuTopbarMenuMenuItems(Request $request): array
    {
        $perPage = 20;
        $page    = max((int) $request->input('page', 1), 1);

        $languageCode   = SystemHelper::LANGUAGE_DEFAULT_CODE;
        $topbarMenuCode = MenuHelper::MENU_TYPE_TOPBAR;

        $cacheKey = "site topbar {$languageCode} {$topbarMenuCode} page {$page} per page {$perPage}";

        $cacheTags = [
            'site',
            'site-topbar',
            'site-topbar-menu',
            'site-topbar-menu-items',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $query = MenuItem::query()
            ->with([
                'menu.language',
                'menu.menuType',
                'children',
                'model',
            ])
            ->whereNull("parent_id")
            ->whereRelation('menu.language', 'code', $languageCode)
            ->whereRelation('menu.menuType', 'name', $topbarMenuCode)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        $list = $query->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
            'public_url'      => $row->public_url,
        ]);

        $data = [
            'items'        => $list,
            'total'        => $query->total(),
            'current_page' => $query->currentPage(),
            'last_page'    => $query->lastPage(),
            'per_page'     => $query->perPage(),
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function menuFooterMenuMenuItems(Request $request): array
    {
        $perPage = 20;
        $page    = max((int) $request->input('page', 1), 1);

        $languageCode   = SystemHelper::LANGUAGE_DEFAULT_CODE;
        $footerMenuCode = MenuHelper::MENU_TYPE_FOOTER;

        $cacheKey = "theme footer {$languageCode} {$footerMenuCode} page {$page} per page {$perPage}";

        $cacheTags = [
            'theme',
            'theme-footer',
            'theme-footer-menu',
            'theme-footer-menu-items',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $query = MenuItem::query()
            ->with([
                'menu.language',
                'menu.menuType',
                'children',
                'model',
            ])
            ->whereNull("parent_id")
            ->whereRelation('menu.language', 'code', $languageCode)
            ->whereRelation('menu.menuType', 'name', $footerMenuCode)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        $list = $query->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
            'public_url'      => $row->public_url,
        ]);

        $data = [
            'items'        => $list,
            'total'        => $query->total(),
            'current_page' => $query->currentPage(),
            'last_page'    => $query->lastPage(),
            'per_page'     => $query->perPage(),
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function menuItemSubMenuItems(Request $request, MenuItem $menuItem): array
    {
        $perPage = 10;
        $page    = max((int) $request->input('page', 1), 1);

        $languageCode        = $menuItem->language->code;
        $menuType            = $menuItem->menu->menuType->name;
        $menuTypeFormatedTag = Str::lower(Str::slug($menuType));

        $cacheKey = "site {$menuType} menu {$menuItem->id} submenus ({$languageCode}) page {$page} per page {$perPage}";

        $cacheTags = [
            'site',
            "site-{$menuTypeFormatedTag}",
            "site-{$menuTypeFormatedTag}-menu-item-{$menuItem->id}",
            "site-{$menuTypeFormatedTag}-menu-item-{$menuItem->id}-menu-items",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $records = MenuItem::query()
            ->with([
                'children',
            ])
            ->where('parent_id', $menuItem->id)
            ->where('language_id', $menuItem->language_id)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        $list = $records->getCollection()->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
            'public_url'      => $row->public_url,
        ]);

        $data = [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
            'per_page'     => $records->perPage(),
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function settings()
    {
        $cacheKey = 'site settings';

        $cacheTags = [
            'site',
            'site-settings',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $data = Setting::query()
            ->orderBy('id', 'asc')
            ->get();

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function breakingNewses(Request $request)
    {
        $perPage = 15;

        $cursor    = $request->input('cursor');
        $cursorKey = $cursor ? md5($cursor) : 'first';

        $languageCode = SystemHelper::LANGUAGE_DEFAULT_CODE;

        $cacheKey = "site breaking newses {$languageCode} cursor {$cursorKey} per page {$perPage}";

        $cacheTags = [
            'site',
            'site-breaking-newses',
            'site-breaking-newses-slider',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $records = BreakingNews::query()
            ->with([
                'news',
                'language',
                'news.language',
            ])
            ->where('is_published', true)
            ->whereRelation('language', 'code', $languageCode)
            ->whereRelation('news.language', 'code', $languageCode)
            ->orderByDesc('created_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage, ['*'], 'cursor');

        CacheServerHelper::cachedData(
            $cacheKey,
            $records,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $records;
    }

}
