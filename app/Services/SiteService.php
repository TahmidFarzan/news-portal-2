<?php
namespace App\Services;

use App\Helpers\CacheServerHelper;
//use App\Models\Language;
//use App\Models\Menu;
use App\Helpers\SystemHelper;
use App\Models\MenuItem;
use Illuminate\Http\Request;

class SiteService
{
    public function menuItem(string $slug): MenuItem
    {
        return MenuItem::where('slug', $slug)->firstOrFail();
    }

    public function themeHeaderMenuMenuItems(Request $request): array
    {
        $perPage = 10;
        $page    = max((int) $request->input('page', 1), 1);

        $languageCode   = SystemHelper::LANGUAGE_DEFAULT_CODE;
        $headerMenuCode = SystemHelper::MENU_TYPE_HEADER;

        $cacheKey = "theme header navbar {$languageCode} {$headerMenuCode} page {$page} per page {$perPage}";

        $cacheTags = [
            'theme',
            'theme-header-navbar',
            'menu-items',
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

    public function themeOffCanvasMenuMenuItems(Request $request): array
    {
        $perPage = 20;
        $page    = max((int) $request->input('page', 1), 1);

        $languageCode   = SystemHelper::LANGUAGE_DEFAULT_CODE;
        $offcanvasMenuCode = SystemHelper::MENU_TYPE_OFFCANVAS;

        $cacheKey = "theme offcanvas {$languageCode} {$offcanvasMenuCode} page {$page} per page {$perPage}";

        $cacheTags = [
            'theme',
            'theme-offcanvas',
            'menu-items',
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

    public function themeMenuItemSubMenuItems(Request $request, MenuItem $menuItem): array
    {
        $perPage = 10;
        $page    = max((int) $request->input('page', 1), 1);

        $languageCode   = SystemHelper::LANGUAGE_DEFAULT_CODE;
        $headerMenuCode = SystemHelper::MENU_TYPE_HEADER;

        $cacheKey = "theme submenu items menu item {$menuItem->id} {$languageCode} {$headerMenuCode} page {$page} per page {$perPage}";

        $cacheTags = [
            'theme',
            'theme-header-navbar',
            'menu-items',
            "menu-item-{$menuItem->id}",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $records = MenuItem::query()
            ->with([
                'menu.language',
                'menu.menuType',
                'children',
                'model',
            ])
            ->where('parent_id', $menuItem->id)
            ->whereRelation('menu.language', 'code', $languageCode)
            ->whereRelation('menu.menuType', 'name', $headerMenuCode)
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

}
