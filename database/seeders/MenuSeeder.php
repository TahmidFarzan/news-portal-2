<?php
namespace Database\Seeders;

use App\Helpers\SystemHelper;
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

        $menuTypeHeader = MenuType::where("name", SystemHelper::MENU_TYPE_HEADER)->first();
        $menuTypeTopBar = MenuType::where("name", SystemHelper::MENU_TYPE_TOPBAR)->first();
        $menuTypeFooter = MenuType::where("name", SystemHelper::MENU_TYPE_FOOTER)->first();
        $menuTypeOffCanvas = MenuType::where("name", SystemHelper::MENU_TYPE_OFFCANVAS)->first();

        foreach ($languages as $language) {

            if ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) {
                Menu::factory()->state([
                    'name'         => "Header",
                    "language_id"  => $language->id,
                    'menu_type_id' => $menuTypeHeader?->id,
                ])->create();

                Menu::factory()->state([
                    'name'         => "Top bar",
                    "language_id"  => $language->id,
                    'menu_type_id' => $menuTypeTopBar?->id,
                ])->create();

                Menu::factory()->state([
                    'name'         => "OffCanvas",
                    "language_id"  => $language->id,
                    'menu_type_id' => $menuTypeOffCanvas?->id,
                ])->create();

                Menu::factory()->state([
                    'name'         => "Footer",
                    "language_id"  => $language->id,
                    'menu_type_id' => $menuTypeFooter?->id,
                ])->create();
            }

            if ($language->code == SystemHelper::LANGUAGE_EXTRA_BN_CODE) {
                Menu::factory()->state([
                    'name'         => "হেডার",
                    "language_id"  => $language->id,
                    'menu_type_id' => $menuTypeHeader?->id,
                ])->create();

                Menu::factory()->state([
                    'name'         => "উপরের বার",
                    "language_id"  => $language->id,
                    'menu_type_id' => $menuTypeTopBar?->id,
                ])->create();

                Menu::factory()->state([
                    'name'         => "অফক্যানভাস",
                    "language_id"  => $language->id,
                    'menu_type_id' => $menuTypeOffCanvas?->id,
                ])->create();

                Menu::factory()->state([
                    'name'         => "ফুটার",
                    "language_id"  => $language->id,
                    'menu_type_id' => $menuTypeFooter?->id,
                ])->create();
            }
        }

        $languages = Language::orderBy('id', "desc")->get();

        foreach ($languages as $language) {
            $menu       = Menu::where("language_id", $language->id)->where('menu_type_id', $menuTypeHeader->id)->first();
            $categories = Category::inRandomOrder()->where("language_id", $language->id)->whereNull("parent_id")->limit(6)->get();

            MenuItem::factory()->state([
                'name'        => ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Home" : "হোম",
                'language_id' => $language->id,
                "parent_id"   => null,

                "menu_id"     => $menu->id,

                "model_type"  => null,
                "model_id"    => null,

                "url"         => route("home"),

                'name_tree'   => ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Home" : "হোম",
                'slug_tree'   => ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Home" : "হোম",
            ])->create();

            MenuItem::factory()->state([
                'name'        => ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Latest" : "সর্বশেষ",
                'language_id' => $language->id,
                "parent_id"   => null,

                "menu_id"     => $menu->id,

                "model_type"  => null,
                "model_id"    => null,

                "url"         => route("home"),

                'name_tree'   => ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Latest" : "সর্বশেষ",
                'slug_tree'   => ($language->code == SystemHelper::LANGUAGE_DEFAULT_CODE) ? "Latest" : "সর্বশেষ",
            ])->create();

            foreach ($categories as $category) {
                $this->saveMenuItem($menu, null, $category);
            }
        }

        foreach ($languages as $language) {
            $menu       = Menu::where("language_id", $language->id)->where('menu_type_id', $menuTypeOffCanvas->id)->first();
            $categories = Category::inRandomOrder()->where("language_id", $language->id)->whereNull("parent_id")->get();

            foreach ($categories as $category) {
                $this->saveMenuItem($menu, null, $category);
            }
        }
    }

    public function saveMenuItem(Menu $menu, ?MenuItem $parent, Category $category)
    {
        $saveMenuItem = MenuItem::factory()->state([
            'name'        => $category->name,
            'language_id' => $category->language_id,
            "parent_id"   => $parent?->id,

            "menu_id"     => $menu->id,

            "model_type"  => $category?->getMorphClass(),
            "model_id"    => $category?->id,

            "url"         => null,

            'name_tree'   => ($parent ? $parent->name . ' - ' : '') . $category->name,
            'slug_tree'   => ($parent ? $parent->slug . '/' : '') . Str::slug($category->name),
        ])->create();

        if (! empty($category->descendants)) {
            foreach ($category->descendants as $subCategory) {
                $this->saveMenuItem($menu, $saveMenuItem, $subCategory);
            }
        }
    }
}
