<?php
namespace Database\Factories;

use App\Helpers\SystemHelper;
use App\Helpers\UserHelper;
use App\Models\Language;
use App\Models\MenuType;
use App\Models\MenuItem;
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
        $language      = Language::where("code", SystemHelper::LANGUAGE_DEFAULT_CODE)->first() ?? null;

        $menuTypes      = [SystemHelper::MENU_TYPE_HEADER, SystemHelper::MENU_TYPE_TOPBAR, SystemHelper::MENU_TYPE_FOOTER];
        $randomMenuType = $menuTypes[array_rand($menuTypes)];

        $menu = MenuType::inRandomOrder()->where("name", $randomMenuType)->first() ?? null;
        return [
            'name'          => $this->faker->name(),
            "menu_id"       => $menu->id,
            "language_id"   => $language->id,
            'url'           => null,
            "created_by_id" => $user?->id ?? 1,
        ];
    }
}
