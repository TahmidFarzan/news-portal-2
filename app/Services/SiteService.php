<?php
namespace App\Services;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Helpers\GoogleAdsenceHelper;
use App\Helpers\MenuHelper;
use App\Helpers\ThemeHelper;

use App\Models\GoogleAdsence;
use App\Models\Language;
use App\Models\MenuItem;
use App\Services\Cache\MenuCacheService;
use App\Services\Cache\NewsCacheService;
use App\Services\Cache\ThemeCacheService;
use App\Services\Cache\GoogleAdsenceCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteService
{
    protected int $cachedTTL = 300;
    protected MenuCacheService $menuCacheService;
    protected ThemeCacheService $themeCacheService;
    protected NewsCacheService $newsCacheService;
    protected GoogleAdsenceCacheService $googleAdsenceCacheService;

    public function __construct(
        MenuCacheService $menuCacheService,
        ThemeCacheService $themeCacheService,
        NewsCacheService $newsCacheService,
        GoogleAdsenceCacheService $googleAdsenceCacheService
    ) {
        $this->menuCacheService  = $menuCacheService;
        $this->themeCacheService = $themeCacheService;
        $this->newsCacheService  = $newsCacheService;
        $this->googleAdsenceCacheService  = $googleAdsenceCacheService;
    }

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
        return $this->menuCacheService->getMenuItemBySlug(CacheHelper::KEY_LAYOUT, $slug, $this->language(), $this->cachedTTL);
    }

    public function menuHeaderMenuMenuItems(): array
    {
        $menu  = $this->menuCacheService->getMenuByMenuTypeCode(CacheHelper::KEY_LAYOUT, MenuHelper::MENU_TYPE_HEADER, $this->language(), $this->cachedTTL);
        $query = $this->menuCacheService->getMenuItems(CacheHelper::KEY_LAYOUT, $menu, null, $this->language(), 10, $this->cachedTTL);

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

        return $data;
    }

    public function menuOffCanvasMenuMenuItems(): array
    {
        $menu  = $this->menuCacheService->getMenuByMenuTypeCode(CacheHelper::KEY_LAYOUT, MenuHelper::MENU_TYPE_OFFCANVAS, $this->language(), $this->cachedTTL);
        $query = $this->menuCacheService->getMenuItems(CacheHelper::KEY_LAYOUT, $menu, null, $this->language(), 20, $this->cachedTTL);

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
        return $data;
    }

    public function menuTopbarMenuMenuItems(): array
    {
        $menu  = $this->menuCacheService->getMenuByMenuTypeCode(CacheHelper::KEY_LAYOUT, MenuHelper::MENU_TYPE_TOPBAR, $this->language(), $this->cachedTTL);
        $query = $this->menuCacheService->getMenuItems(CacheHelper::KEY_LAYOUT, $menu, null, $this->language(), 20, $this->cachedTTL);

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

        return $data;
    }

    public function menuFooterMenuMenuItems(): array
    {
        $menu  = $this->menuCacheService->getMenuByMenuTypeCode(CacheHelper::KEY_LAYOUT, MenuHelper::MENU_TYPE_FOOTER, $this->language(), $this->cachedTTL);
        $query = $this->menuCacheService->getMenuItems(CacheHelper::KEY_LAYOUT, $menu, null, $this->language(), 20, $this->cachedTTL);

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

        return $data;
    }

    public function menuItemSubMenuItems(MenuItem $menuItem): array
    {
        $records = $this->menuCacheService->getMenuItems(CacheHelper::KEY_LAYOUT, $menuItem->menu, $menuItem, $menuItem->language, 10, $this->cachedTTL);

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

        return $data;
    }

    public function themes()
    {
        return $this->themeCacheService->getThemes(
            CacheHelper::KEY_LAYOUT,
            CacheServerHelper::sixHoursInSecond
        );
    }

    public function themeHeader()
    {
        $labels = [
            ThemeHelper::OPTION_GOOGLE_SEARCH_CONSOLE_HEADER,
            ThemeHelper::OPTION_GOOGLE_ANALYTIC_HEADER,
            ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_HEADER,
        ];

        return $this->themeCacheService->getThemesByGroupAndLabels(
            CacheHelper::KEY_LAYOUT,
            ThemeHelper::GROUP_APP,
            $labels,
            CacheServerHelper::sixHoursInSecond
        );
    }

    public function themeBody()
    {
        $labels = [
            ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_BODY,
        ];

        return $this->themeCacheService->getThemesByGroupAndLabels(
            CacheHelper::KEY_LAYOUT,
            ThemeHelper::GROUP_APP,
            $labels,
            CacheServerHelper::sixHoursInSecond
        );
    }

    public function themeGoogleAdCLientId()
    {
        return $this->themeCacheService->getThemeByGroupAndLabel(
            CacheHelper::KEY_LAYOUT,
            ThemeHelper::GROUP_APP,
            ThemeHelper::OPTION_GOOGLE_ADSENCE_CLIENT_ID,
            CacheServerHelper::sixHoursInSecond
        );
    }

    public function breakingNews(Request $request)
    {
        return $this->newsCacheService->getBreakingNews(
            CacheHelper::KEY_SITE,
            $request,
            $this->language(),
            10,
            $this->cachedTTL
        );
    }

    public function getGoogleAdsence(Request $request)
    {
        $type     = $request->input("type", GoogleAdsenceHelper::TYPE_SECTION);
        $position = $request->input("position", GoogleAdsenceHelper::POSITION_TOP);

        return $this->googleAdsenceCacheService->getGoogleAdsencesByTypeAndPosition(
            CacheHelper::KEY_LAYOUT,
            $type,
            $position,
            $this->cachedTTL
        );
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
}
