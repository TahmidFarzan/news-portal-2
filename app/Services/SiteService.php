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

        $cacheKey = "site:{$headerMenuCode}:navbar:language:{$languageCode}:menu:{$headerMenuCode}:page:{$page}:per-page:{$perPage}";

        $cacheTags = [
            'site',
            "site:{$headerMenuCode}",
            "site:{$headerMenuCode}:navbar",
            "site:{$headerMenuCode}:navbar:language:{$languageCode}",
            "site:{$headerMenuCode}:navbar:language:{$languageCode}:menu:{$headerMenuCode}",
            "site:{$headerMenuCode}:navbar:language:{$languageCode}:menu:{$headerMenuCode}:menu-items",
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

        $cacheKey = "site:{$offcanvasMenuCode}:language:{$languageCode}:page:{$page}:per-page:{$perPage}";

        $cacheTags = [
            'site',
            "site:{$offcanvasMenuCode}",
            "site:{$offcanvasMenuCode}:navbar",
            "site:{$offcanvasMenuCode}:navbar:language:{$languageCode}",
            "site:{$offcanvasMenuCode}:navbar:language:{$languageCode}:menu:{$offcanvasMenuCode}",
            "site:{$offcanvasMenuCode}:navbar:language:{$languageCode}:menu:{$offcanvasMenuCode}:menu-items",
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

        $cacheKey = "site:header:{$topbarMenuCode}:language:{$languageCode}:page:{$page}:per-page:{$perPage}";

        $cacheTags = [
            'site',
            "site:header",
            "site:header:{$topbarMenuCode}",
            "site:header:{$topbarMenuCode}:navbar",
            "site:header:{$topbarMenuCode}:navbar:language:{$languageCode}",
            "site:header:{$topbarMenuCode}:navbar:language:{$languageCode}:menu:{$topbarMenuCode}",
            "site:header:{$topbarMenuCode}:navbar:language:{$languageCode}:menu:{$topbarMenuCode}:menu-items",
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

        $cacheKey = "site:{$footerMenuCode}:language:{$languageCode}:page:{$page}:per-page:{$perPage}";

        $cacheTags = [
            'site',
            "site:{$footerMenuCode}",
            "site:{$footerMenuCode}:navbar",
            "site:{$footerMenuCode}:navbar:language:{$languageCode}",
            "site:{$footerMenuCode}:navbar:language:{$languageCode}:menu:{$footerMenuCode}",
            "site:{$footerMenuCode}:navbar:language:{$languageCode}:menu:{$footerMenuCode}:menu-items",
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

        $languageCode = $menuItem->language->code;

        $menu     = $menuItem->menu;
        $menuType = $menu->menuType->name;

        $cacheKey = "site:menu-type:{$menuType->slug}:menu:{$menu->slug}:language:{$languageCode}:menu-item:{$menuItem}:page:{$page}:per-page:{$perPage}";

        $cacheTags = [
            'site',
            'site:menu',
            "site:menu-type:{$menuType->slug}",
            "site:menu-type:{$menuType->slug}:menu:{$menu->slug}",
            "site:menu-type:{$menuType->slug}:menu:{$menu->slug}:language-{$languageCode}",
            "site:menu-type:{$menuType->slug}:menu:{$menu->slug}:language:{$languageCode}:menu-item:{$menuItem}",
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
        $cacheKey = 'site:settings';

        $cacheTags = [
            'site',
            'site:settings',
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

    public function breakingNews(Request $request)
    {
        $perPage = 15;

        $cursor    = $request->input('cursor');
        $cursorKey = $cursor ? md5($cursor) : 'first';

        $languageCode = SystemHelper::LANGUAGE_DEFAULT_CODE;

        $cacheKey = "site:breaking-news:language:{$languageCode}:cursor:{$cursorKey}:per-page:{$perPage}";

        $cacheTags = [
            'site',
            'site-breaking-news',
            "site:breaking-news:language:{$languageCode}",
            "site:breaking-news:language:{$languageCode}:news-slider",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $query = BreakingNews::query()
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
            $query,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $query;
    }

}
