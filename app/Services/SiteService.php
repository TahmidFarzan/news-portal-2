<?php
namespace App\Services;

use App\Helpers\CacheHelper;
use App\Helpers\CacheServerHelper;
use App\Helpers\GoogleAdsenseHelper;
use App\Helpers\MenuHelper;
use App\Helpers\SystemHelper;
use App\Helpers\ThemeHelper;
use App\Models\Language;
use App\Models\MenuItem;
use App\Models\Theme;
use App\Services\Cache\GoogleAdsenseCacheService;
use App\Services\Cache\MenuCacheService;
use App\Services\Cache\NewsCacheService;
use App\Services\Cache\ThemeCacheService;
use App\Services\Cache\LanguageCacheService;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class SiteService
{
    protected int $cachedTTL = 300;

    protected MenuCacheService $menuCacheService;

    protected ThemeCacheService $themeCacheService;

    protected NewsCacheService $newsCacheService;

    protected GoogleAdsenseCacheService $googleAdsenseCacheService;

        protected LanguageCacheService $languageCacheService;

    public function __construct(
        MenuCacheService $menuCacheService,
        ThemeCacheService $themeCacheService,
        NewsCacheService $newsCacheService,
        GoogleAdsenseCacheService $googleAdsenseCacheService,
        LanguageCacheService $languageCacheService
    ) {
        $this->menuCacheService          = $menuCacheService;
        $this->themeCacheService         = $themeCacheService;
        $this->newsCacheService          = $newsCacheService;
        $this->googleAdsenseCacheService = $googleAdsenseCacheService;
        $this->languageCacheService = $languageCacheService;
    }

    public function language(string $code): Language
    {
        $language = $this->languageCacheService->getRecordByCodeFirst(CacheHelper::KEY_LAYOUT, $code ,$this->cachedTTL);
        if (! $language) {
            return $this->defaultLanguage();
        }

        return $language;
    }

    public function defaultLanguage(): Language
    {
        return $this->languageCacheService->getRecordByDefault(CacheHelper::KEY_LAYOUT, $this->cachedTTL);
    }

    public function menuItem(Language $language, string $slug): MenuItem
    {
        return $this->menuCacheService->getMenuItemBySlug(CacheHelper::KEY_LAYOUT, $language, $slug, $this->cachedTTL);
    }

    public function menuHeaderMenuMenuItems(Language $language): array
    {
        $menu  = $this->menuCacheService->getMenuByMenuTypeCode(CacheHelper::KEY_LAYOUT, $language, MenuHelper::MENU_TYPE_HEADER, $this->cachedTTL);
        $query = $this->menuCacheService->getMenuItems(CacheHelper::KEY_LAYOUT, $language, $menu, null, 10, $this->cachedTTL);

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

    public function menuOffCanvasMenuMenuItems(Language $language): array
    {
        $menu  = $this->menuCacheService->getMenuByMenuTypeCode(CacheHelper::KEY_LAYOUT, $language, MenuHelper::MENU_TYPE_OFFCANVAS, $this->cachedTTL);
        $query = $this->menuCacheService->getMenuItems(CacheHelper::KEY_LAYOUT, $language, $menu, null, 20, $this->cachedTTL);

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

    public function menuTopbarMenuMenuItems(Language $language): array
    {
        $menu  = $this->menuCacheService->getMenuByMenuTypeCode(CacheHelper::KEY_LAYOUT, $language, MenuHelper::MENU_TYPE_TOPBAR, $this->cachedTTL);
        $query = $this->menuCacheService->getMenuItems(CacheHelper::KEY_LAYOUT, $language, $menu, null, 20, $this->cachedTTL);

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

    public function menuFooterMenuMenuItems(Language $language): array
    {
        $menu  = $this->menuCacheService->getMenuByMenuTypeCode(CacheHelper::KEY_LAYOUT, $language, MenuHelper::MENU_TYPE_FOOTER, $this->cachedTTL);
        $query = $this->menuCacheService->getMenuItems(CacheHelper::KEY_LAYOUT, $language, $menu, null, 20, $this->cachedTTL);

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
        $records = $this->menuCacheService->getMenuItems(CacheHelper::KEY_LAYOUT, $menuItem->language, $menuItem->menu, $menuItem, 10, $this->cachedTTL);

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

    public function themes(): Collection
    {
        return $this->themeCacheService->getThemes(
            CacheHelper::KEY_LAYOUT,
            CacheServerHelper::sixHoursInSecond
        );
    }

    public function themeHeader(): Collection
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

    public function themeBody(): Collection
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

    public function themeGoogleAdsenseClientId(): Theme
    {
        return $this->themeCacheService->getThemeByGroupAndLabel(
            CacheHelper::KEY_LAYOUT,
            ThemeHelper::GROUP_APP,
            ThemeHelper::OPTION_GOOGLE_ADSENSE_CLIENT_ID,
            CacheServerHelper::sixHoursInSecond
        );
    }

    public function breakingNews(Request $request, Language $language): CursorPaginator
    {
        return $this->newsCacheService->getBreakingNews(
            CacheHelper::KEY_SITE,
            $language,
            $request,
            10,
            $this->cachedTTL
        );
    }

    public function getGoogleAdsense(Request $request): Collection
    {
        $type     = $request->input('type', GoogleAdsenseHelper::TYPE_SECTION);
        $position = $request->input('position', GoogleAdsenseHelper::POSITION_TOP);

        return $this->googleAdsenseCacheService->getGoogleAdsensesByTypeAndPosition(
            CacheHelper::KEY_LAYOUT,
            $type,
            $position,
            $this->cachedTTL
        );
    }

    public function languages(Request $request): array
    {
        $records = $this->languageCacheService->getRecords(CacheHelper::KEY_LAYOUT, $request ,$this->cachedTTL);

        $list = $records->map(fn($row) => [
            'id'     => $row->id,
            'name'   => $row->name,
            'code'   => $row->code,
            'locale' => $row->locale,
            'slug'   => $row->slug,
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

}
