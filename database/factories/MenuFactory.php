<?php
namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Helpers\UserHelper;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuType;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Menu>
 */
class MenuFactory extends Factory
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
        $language      = Language::where("code", SeederHelper::LANGUAGE_EN_CODE)->first() ?? null;
        $menuType      = MenuType::inRandomOrder()->first();

        return [
            'name'          => $this->faker->name(),
            "language_id"   => $language->id,
            'menu_type_id'  => $menuType?->id ?? 1,
            "created_by_id" => $user?->id ?? 1,
        ];
    }
}
