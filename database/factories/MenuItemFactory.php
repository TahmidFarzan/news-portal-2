<?php
namespace Database\Factories;

use App\Helpers\SystemHelper;
use App\Helpers\MenuHelper;
use App\Helpers\UserHelper;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\MenuType;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $adminUserRole = UserRole::where("name", UserHelper::USER_ROLE_ADMIN)->inRandomOrder()->first();
        $user          = User::inRandomOrder()->where("user_role_id", $adminUserRole->id)->first() ?? null;
        $language      = Language::where("code", SystemHelper::DEFAULT_LANGUAGE_CODE)->first() ?? null;

        $menuTypes      = [MenuHelper::MENU_TYPE_HEADER, MenuHelper::MENU_TYPE_TOPBAR, MenuHelper::MENU_TYPE_FOOTER];
        $randomMenuType = $menuTypes[array_rand($menuTypes)];
        $menuType = MenuType::inRandomOrder()->where("name", $randomMenuType)->first() ?? null;

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
