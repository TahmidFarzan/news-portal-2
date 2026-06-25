<?php
namespace Database\Factories;

use App\Helpers\MenuHelper;
use App\Helpers\SeederHelper;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user     = User::where("is_super_admin", true)->inRandomOrder()->first();
        $language = Language::where("code", SeederHelper::LANGUAGE_EN_CODE)->first() ?? null;

        $menuTypes      = [MenuHelper::MENU_TYPE_HEADER, MenuHelper::MENU_TYPE_TOPBAR, MenuHelper::MENU_TYPE_FOOTER];
        $randomMenuType = $menuTypes[array_rand($menuTypes)];
        $menuType       = MenuType::inRandomOrder()->where("name", $randomMenuType)->first() ?? null;

        $menu = Menu::inRandomOrder()->where("menu_type_id", $menuType?->id)->first() ?? null;
        return [
            'name'          => $this->faker->name(),
            "menu_id"       => $menu->id,
            "language_id"   => $language->id,
            'url'           => null,
            'position'      => ($this->menuItemLastPosition($menu, $language->id, null) + 1) ?? null,
            "created_by_id" => $user?->id ?? 1,
        ];
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
