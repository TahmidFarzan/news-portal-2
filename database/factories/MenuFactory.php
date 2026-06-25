<?php
namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuType;
use App\Models\User;
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
        $user     = User::where("is_super_admin", true)->inRandomOrder()->first();
        $language = Language::where("code", SeederHelper::LANGUAGE_EN_CODE)->first() ?? null;
        $menuType = MenuType::inRandomOrder()->first();

        return [
            'name'          => $this->faker->name(),
            "language_id"   => $language->id,
            'menu_type_id'  => $menuType?->id ?? 1,
            "created_by_id" => $user?->id ?? 1,
        ];
    }
}
