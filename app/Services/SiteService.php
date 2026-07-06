<?php
namespace App\Services;

use App\Helpers\CacheServerHelper;
use App\Helpers\GoogleAdsenceHelper;
use App\Helpers\MenuHelper;
use App\Helpers\ThemeHelper;
use App\Models\BreakingNews;
use App\Models\GoogleAdsence;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;

use App\Models\Theme;


use Illuminate\Http\Request;

use Illuminate\Support\Str;

class SiteService
{
    public function language(): Language
    {
        $languageId = session('selected_language_id');

        if ($languageId) {
            return Language::select([
                'id',
                'name',
                'code',
                "locale",
                'slug',
            ])
                ->where('id', $languageId)
                ->firstOrFail();
        }

        return Language::select([
            'id',
            'name',
            'code',
            "locale",
            'slug',
        ])
            ->oldest('id')
            ->firstOrFail();
    }

    public function menuItem(string $slug): MenuItem
    {
        return MenuItem::select([
            'id',
            'name',
            'slug',
            'url',
            'language_id',
            'model_type',
            'model_id',
            'menu_id',
        ])
            ->with([
                'model:id,slug',
                'language:id,name,code,locale,slug',
                'menu:id,name,language_id,slug,menu_type_id',
                'menu.menuType:id,name,slug',
            ])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function menuHeaderMenuMenuItems(Request $request): array
    {
        $perPage = 10;
        $page    = max((int) $request->input('page', 1), 1);

        $language          = $this->language();
        $headerMenuCode    = MenuHelper::MENU_TYPE_HEADER;
        $headerMenuCodeKey = Str::lower($headerMenuCode);

        $cacheKey = "site:language:{$language->locale}:{$headerMenuCodeKey}:menu-items:per-page:{$perPage}:page:{$page}";

        $cacheTags = [
            "site",
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:{$headerMenuCodeKey}",
            "site:language:{$language->locale}:{$headerMenuCodeKey}:menu-items",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $menu = Menu::with(['menuType'])->select(["id", "name", 'slug', "language_id"])->where("language_id", $language->id)->whereRelation('menuType', 'name', $headerMenuCode)->firstOrFail();

        $query = MenuItem::select([
            'id',
            'name',
            "slug",
            'url',
            "model_type",
            "model_id",
            'position',
        ])
            ->with([
                'model',
            ])
            ->whereNull("parent_id")
            ->where('menu_id', $menu->id)
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

        $language             = $this->language();
        $offcanvasMenuCode    = MenuHelper::MENU_TYPE_OFFCANVAS;
        $offcanvasMenuCodeKey = Str::lower($offcanvasMenuCode);

        $cacheKey = "site:language:{$language->locale}:{$offcanvasMenuCodeKey}:menu-items:per-page:{$perPage}:page:{$page}";

        $cacheTags = [
            "site",
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:{$offcanvasMenuCodeKey}",
            "site:language:{$language->locale}:{$offcanvasMenuCodeKey}:menu-items",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }
        $menu = Menu::with(['menuType'])->select(["id", "name", 'slug', "language_id"])->where("language_id", $language->id)->whereRelation('menuType', 'name', $offcanvasMenuCode)->firstOrFail();

        $query = MenuItem::select([
            'id',
            'name',
            "slug",
            'url',
            "model_type",
            "model_id",
            'position',
        ])
            ->with([
                'model',
            ])
            ->whereNull("parent_id")
            ->where('menu_id', $menu->id)
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

        $language          = $this->language();
        $topbarMenuCode    = MenuHelper::MENU_TYPE_TOPBAR;
        $topbarMenuCodeKey = Str::lower($topbarMenuCode);

        $cacheKey = "site:language:{$language->locale}:{$topbarMenuCodeKey}:menu-items:per-page:{$perPage}:page:{$page}";

        $cacheTags = [
            "site",
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:{$topbarMenuCodeKey}",
            "site:language:{$language->locale}:{$topbarMenuCodeKey}:menu-items",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $menu = Menu::with(['menuType'])->select(["id", "name", 'slug', "language_id"])->where("language_id", $language->id)->whereRelation('menuType', 'name', $topbarMenuCode)->firstOrFail();

        $query = MenuItem::select([
            'id',
            'name',
            "slug",
            'url',
            "model_type",
            "model_id",
            'position',
        ])
            ->with([
                'model',
            ])
            ->whereNull("parent_id")
            ->where('menu_id', $menu->id)
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

        $language          = $this->language();
        $footerMenuCode    = MenuHelper::MENU_TYPE_FOOTER;
        $footerMenuCodeKey = Str::lower($footerMenuCode);

        $cacheKey = "site:language:{$language->locale}:{$footerMenuCodeKey}:menu-items:per-page:{$perPage}:page:{$page}";

        $cacheTags = [
            "site",
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:{$footerMenuCodeKey}",
            "site:language:{$language->locale}:{$footerMenuCodeKey}:menu-items",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $menu = Menu::with(['menuType'])->select(["id", "name", 'slug', "language_id"])->where("language_id", $language->id)->whereRelation('menuType', 'name', $footerMenuCode)->firstOrFail();

        $query = MenuItem::select([
                'id',
                "url",
                'name',
                "slug",
                "model_type",
                "model_id",
                'position',
            ])
            ->with([
                'model',
            ])
            ->whereNull("parent_id")
            ->where('menu_id', $menu->id)
            ->whereRelation('menu.language', 'id', $language->id)
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

        $language = $menuItem->language;

        $menu     = $menuItem->menu;
        $menuType = $menu->menuType;

        $cacheKey = "site:language:{$language->locale}:menu-type:{$menuType->slug}:menu:{$menu->slug}:menu-item:{$menuItem->slug}:menu-items:per-page:{$perPage}:page:{$page}";

        $cacheTags = [
            'site',
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:menu-type:{$menuType->slug}",
            "site:language:{$language->locale}:menu-type:{$menuType->slug}:menu:{$menu->slug}",
            "site:language:{$language->locale}:menu-type:{$menuType->slug}:menu:{$menu->slug}:menu-item:{$menuItem->slug}",
            "site:language:{$language->locale}:menu-type:{$menuType->slug}:menu:{$menu->slug}:menu-item:{$menuItem->slug}:menu-items",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $records = MenuItem::query()
            ->with([
                'model',
            ])
            ->select([
                'id',
                'name',
                "slug",
                "model_type",
                "model_id",
                'position',
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

    public function themes()
    {
        $cacheKey = 'site:themes';

        $cacheTags = [
            'site',
            'site:themes',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $data = Theme::select([
            'group',
            'label',
            'type',
            'value',
            'slug',
        ])
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

    public function themeHeader()
    {
        $cacheKey = 'site:theme:header';

        $cacheTags = [
            'site',
            'site:theme',
            'site:theme:header',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }
        $labels = [
            ThemeHelper::OPTION_GOOGLE_SEARCH_CONSOLE_HEADER,
            ThemeHelper::OPTION_GOOGLE_ANALYTIC_HEADER,
            ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_HEADER,
        ];

        $data = Theme::select([
            'group',
            'label',
            'type',
            'value',
            'slug',
        ])->where('group', ThemeHelper::GROUP_APP)->whereIn('label', $labels)->get();

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function themeBody()
    {
        $cacheKey = 'site:theme:body';

        $cacheTags = [
            'site',
            'site:theme',
            'site:theme:body',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $labels = [
            ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_BODY,
        ];

        $data = Theme::select([
            'group',
            'label',
            'type',
            'value',
            'slug',
        ])->where('group', ThemeHelper::GROUP_APP)->whereIn('label', $labels)->get();

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function themeGoogleAdCLientId()
    {
        $cacheKey = 'site:theme:google:ad-client-id';

        $cacheTags = [
            'site',
            'site:theme',
            'site:theme:google',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $data = Theme::select([
            'group',
            'label',
            'type',
            'value',
            'slug',
        ])->where('group', ThemeHelper::GROUP_APP)->where('label', ThemeHelper::OPTION_GOOGLE_ADSENCE_CLIENT_ID)->firstOrFail();

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
        $perPage = 10;

        $cursor    = $request->input('cursor');
        $cursorKey = $cursor ? md5($cursor) : 'first';

        $language = $this->language();

        $cacheKey = "site:language:{$language->locale}:breaking-news:cursor:{$cursorKey}:per-page:{$perPage}";

        $cacheTags = [
            'site',
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:breaking-news",
            "site:language:{$language->locale}:breaking-news",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $query = BreakingNews::
            select([
            "id",
            "title",
            "slug",
            "language_id",
            "news_id",
        ])
            ->with(
                "news:id,title,slug,language_id,category_id,news_type_id,created_at,updated_at",
                "news.category:id,name,name_tree,slug,slug_tree",
            )
            ->where('is_published', true)
            ->where('language_id', $language->id)
            ->whereRelation('news', 'language_id', $language->id)
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

    public function languages(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $search  = $request->input('search');
        $page    = (int) $request->input('page', 1);

        $cacheSearch = md5($search ?? '');

        $cacheKey = "site:languages:basic-pagination:search:{$cacheSearch}:page:{$page}:per-page:{$perPage}";

        $cacheTags = [
            'site',
            'site.languages',
            'site:languages:basic-pagination',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $query = Language::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $records = $query
            ->orderByDesc('id')
            ->paginate($perPage);

        $list = $records->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
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

    public function languageChange(int | string $slugOrId): array
    {
        $language = Language::query()
            ->where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();

        session()->put('selected_language_id', $language->id);

        return [
            'status'  => true,
            'message' => __('status-messages.site.language.change.success'),
            'data'    => $language,
        ];
    }

    public function getGoogleAdsence(Request $request)
    {
        $type     = $request->input("type", GoogleAdsenceHelper::TYPE_SECTION);
        $position = $request->input("position", GoogleAdsenceHelper::POSITION_TOP);

        $typeCacheKey     = Str::lower($type);
        $positionCacheKey = Str::lower($position);

        $cacheKey = "site:google-adsence:type:{$typeCacheKey}:position:{$positionCacheKey}";

        $cacheTags = [
            'site',
            'site:google-adsence',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $data = GoogleAdsence::select([
            "id",
            'name',
            'slug',
            "slot_id",
            "client_id",
            "type",
            "position",
        ])->where('type', $type)->where('position', $position)->get();

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

}
