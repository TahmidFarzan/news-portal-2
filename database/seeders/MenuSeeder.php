<?php
namespace Database\Seeders;

use App\Helpers\SystemHelper;
use App\Helpers\MenuHelper;
use App\Models\Category;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuType;
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
                'name'         => ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Top bar" : "উপরের বার",
                "language_id"  => $language->id,
                'menu_type_id' => $menuTypeTopBar?->id,
            ])->create();

            $headerMenu = Menu::factory()->state([
                'name'         => ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Header" : "হেডার",
                "language_id"  => $language->id,
                'menu_type_id' => $menuTypeHeader?->id,
            ])->create();

            $offCanvasMenu = Menu::factory()->state([
                'name'         => ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "OffCanvas" : "অফক্যানভাস",
                "language_id"  => $language->id,
                'menu_type_id' => $menuTypeOffCanvas?->id,
            ])->create();

            $footerMenu = Menu::factory()->state([
                'name'         => ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Footer" : "ফুটার",
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
        $this->saveMenuItem($menu, null, $language, ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Contact" : "যোগাযোগ");
        $this->saveMenuItem($menu, null, $language, ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "About" : "সম্পর্কে");
    }

    private function headerMenuItemSave(Menu $menu, Language $language): void
    {
        $categories = Category::inRandomOrder()->where("language_id", $language->id)->whereNull("parent_id")->limit(6)->get();

        $this->saveMenuItem($menu, null, $language, ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Home" : "হোম");
        $this->saveMenuItem($menu, null, $language, ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Latest" : "সর্বশেষ");

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
        $this->saveMenuItem($menu, null, $language, ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Contact" : "যোগাযোগ");
        $this->saveMenuItem($menu, null, $language, ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "About" : "সম্পর্কে");
        $this->saveMenuItem($menu, null, $language, ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Privacy Policy" : "গোপনীয়তা নীতি");
        $this->saveMenuItem($menu, null, $language, ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Terms and Conditions" : "শর্তাবলি ও নীতিমালা");
    }

    private function saveMenuItem(Menu $menu, ?MenuItem $parent, Language $language, Category | string $item): void
    {
        $isCategory = $item instanceof Category;

        $name = $isCategory ? $item->name : $item;

        $saveMenuItem = MenuItem::factory()->state([
            'name'        => $name,
            'language_id' => $language->id,
            'parent_id'   => $parent?->id,

            'menu_id'     => $menu->id,

            'model_type'  => $isCategory ? $item->getMorphClass() : null,
            'model_id'    => $isCategory ? $item->id : null,

            'url'         => $isCategory ? null : route("home"),

            'position'    => $this->menuItemLastPosition($menu, $language->id, $parent?->id) + 1,

            'name_tree'   => ($parent ? $parent->name_tree . ' - ' : '') . $name,
            'slug_tree'   => ($parent ? $parent->slug_tree . '/' : '') . Str::slug($name),
        ])->create();

        if ($isCategory && ! empty($item->descendants)) {
            foreach ($item->descendants as $subCategory) {
                $this->saveMenuItem($menu, $saveMenuItem, $language, $subCategory);
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
