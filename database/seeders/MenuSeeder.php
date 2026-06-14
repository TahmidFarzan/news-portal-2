<?php
namespace Database\Seeders;

use App\Helpers\MenuHelper;
use App\Helpers\PageHelper;
use App\Helpers\SystemHelper;
use App\Models\Category;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuType;
use App\Models\Page;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Menu::query()->delete();
            MenuItem::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='menus'");
            DB::statement("DELETE FROM sqlite_sequence WHERE name='menu_items'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Menu::truncate();
            MenuItem::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            Menu::truncate();
            MenuItem::truncate();
        }

        $languages = Language::orderBy("id", "desc")->get();

        $menuTypeHeader    = MenuType::where("name", MenuHelper::MENU_TYPE_HEADER)->first();
        $menuTypeTopBar    = MenuType::where("name", MenuHelper::MENU_TYPE_TOPBAR)->first();
        $menuTypeFooter    = MenuType::where("name", MenuHelper::MENU_TYPE_FOOTER)->first();
        $menuTypeOffCanvas = MenuType::where("name", MenuHelper::MENU_TYPE_OFFCANVAS)->first();

        foreach ($languages as $language) {

            $topbarMenu = Menu::factory()->state([
                'name'         => ($language->code == SystemHelper::DEFAULT_LANGUAGE_CODE) ? "Top bar" : "উপরের বার",
                "language_id"  => $language->id,
                'menu_type_id' => $menuTypeTopBar?->id,
            ])->create();

            $headerMenu = Menu::factory()->state([
                'name'         => ($language->code == SystemHelper::DEFAULT_LANGUAGE_CODE) ? "Header" : "হেডার",
                "language_id"  => $language->id,
                'menu_type_id' => $menuTypeHeader?->id,
            ])->create();

            $offCanvasMenu = Menu::factory()->state([
                'name'         => ($language->code == SystemHelper::DEFAULT_LANGUAGE_CODE) ? "OffCanvas" : "অফক্যানভাস",
                "language_id"  => $language->id,
                'menu_type_id' => $menuTypeOffCanvas?->id,
            ])->create();

            $footerMenu = Menu::factory()->state([
                'name'         => ($language->code == SystemHelper::DEFAULT_LANGUAGE_CODE) ? "Footer" : "ফুটার",
                "language_id"  => $language->id,
                'menu_type_id' => $menuTypeFooter?->id,
            ])->create();

            $this->topBarMenuItemSave($topbarMenu, $language);
            $this->headerMenuItemSave($headerMenu, $language);
            $this->offcanvasMenuItemSave($offCanvasMenu, $language);
            $this->footerMenuItemSave($footerMenu, $language);
        }

    }

    private function topBarMenuItemSave(Menu $menu, Language $language): void
    {
        $pageNames = [
            SystemHelper::DEFAULT_LANGUAGE_CODE  => [
                "Contact",
                "About",
            ],

            SystemHelper::EXTRA_LANGUAGE_BN_CODE => [
                "সম্পর্কে",
                "যোগাযোগ",
            ],
        ];

        $pages = Page::whereIn("title", $pageNames[$language->code])->where("language_id", $language->id)->where("is_default", false)->where("is_published", true)->get();
        foreach ($pages as $page) {
            $this->saveMenuItem($menu, null, $language, $page);
        }
    }

    private function headerMenuItemSave(Menu $menu, Language $language): void
    {
        $categoryNames = ['National', 'International', 'Business', 'Entertainment', 'Technology', 'Sports', 'জাতীয়', 'আন্তর্জাতিক', 'ব্যবসা', 'বিনোদন', 'প্রযুক্তি', 'খেলাধুলা'];

        $pages = Page::whereIn("default_use_as", [PageHelper::DAFAULT_USE_AS_HOME, PageHelper::DAFAULT_USE_AS_LATEST])->where("language_id", $language->id)->where("is_default", true)->where("is_published", true)->get();

        $categories = Category::query()
            ->where('language_id', $language->id)
            ->whereNull('parent_id')
            ->whereIn('name', $categoryNames)
            ->get();

        foreach ($pages as $page) {
            $this->saveMenuItem($menu, null, $language, $page);
        }

        foreach ($categories as $category) {
            $this->saveMenuItem($menu, null, $language, $category);
        }
    }

    private function offcanvasMenuItemSave(Menu $menu, Language $language): void
    {
        $categories = Category::inRandomOrder()->where("language_id", $language->id)->whereNull("parent_id")->get();
        foreach ($categories as $category) {
            $this->saveMenuItem($menu, null, $language, $category);
        }
    }

    private function footerMenuItemSave(Menu $menu, Language $language): void
    {
        $pageNames = [
            SystemHelper::DEFAULT_LANGUAGE_CODE  => [
                "Contact",
                "About",
                "Privacy Policy",
                "Terms and Conditions",
            ],

            SystemHelper::EXTRA_LANGUAGE_BN_CODE => [
                "সম্পর্কে",
                "যোগাযোগ",
                "গোপনীয়তা নীতি",
                "শর্তাবলি ও নীতিমালা",
            ],
        ];

        $pages = Page::whereIn("title", $pageNames[$language->code])->where("language_id", $language->id)->where("is_default", false)->where("is_published", true)->get();
        foreach ($pages as $page) {
            $this->saveMenuItem($menu, null, $language, $page);
        }
    }

    private function saveMenuItem(Menu $menu, ?MenuItem $parent, Language $language, Category | Page | string $item): void
    {
        $isCategory = $item instanceof Category;
        $isPage     = $item instanceof Page;

        $name = match (true) {
            $isCategory => $item->name,
            $isPage     => $item->title,
            default     => $item,
        };

        $url = match ($name) {
            'Home', 'হোম'       => route('home'),
            'Latest', 'সর্বশেষ' => route('latest'),
            default => null,
        };

        $saveMenuItem = MenuItem::factory()->state([
            'name'        => $name,
            'language_id' => $language->id,
            'parent_id'   => $parent?->id,
            'menu_id'     => $menu->id,

            'model_type'  => ($isCategory || $isPage) ? $item->getMorphClass() : null,
            'model_id'    => ($isCategory || $isPage) ? $item->id : null,

            'url'         => $url,

            'position'    => $this->menuItemLastPosition($menu, $language->id, $parent?->id) + 1,

            'name_tree'   => ($parent ? $parent->name_tree . ' - ' : '') . $name,
            'slug_tree'   => ($parent ? $parent->slug_tree . '/' : '') . Str::slug($name),
        ])->create();

        if (($isCategory || $isPage) && $item->descendants->isNotEmpty()) {
            foreach ($item->descendants as $subItem) {
                $this->saveMenuItem($menu, $saveMenuItem, $language, $subItem);
            }
        }
    }

    private function menuItemLastPosition(Menu $menu, int $languageId, ?int $parentId)
    {
        $position = MenuItem::query()
            ->where('menu_id', $menu->id)
            ->where('language_id', $languageId);

        if ($parentId) {
            $position->where('parent_id', $parentId);
        }
        return $position->max("position");
    }
}
