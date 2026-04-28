<?php
namespace Database\Factories;

use App\Models\Language;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Helpers\SystemHelper;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $adminUserRole = UserRole::where("name", SystemHelper::USER_ROLE_ADMIN)->inRandomOrder()->first();
        $user          = User::inRandomOrder()->where("user_role_id", $adminUserRole->id)->first() ?? null;
        $language      = Language::where("code",SystemHelper::DEFAULT_LANGUAGE_CODE)->first() ?? null;

        $name    = $this->faker->name();
        $details = $this->faker->sentence();

        return [
            'name'          => $name,
            'details'       => $details,

            'name_tree'     => $name,
            'slug_tree'     => Str::slug($name),

            'seo_title'     => $name,
            'seo_brief'     => $details,

            "language_id"   => $language?->id ?? "1",

            "parent_id"     => null,
            "created_by_id" => $user?->id ?? "1",
        ];
    }
}
