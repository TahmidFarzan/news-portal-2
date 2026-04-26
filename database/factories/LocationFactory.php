<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Language;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
class LocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $adminUserRole = UserRole::where("name", "Admin")->inRandomOrder()->first();
        $user          = User::inRandomOrder()->where("user_role_id", $adminUserRole->id)->first() ?? null;
        $language      = Language::where("code","en_us")->first() ?? null;

        $name    = $this->faker->name();
        $details = $this->faker->sentence();

        return [
            'name'          => $name,
            'details'       => $details,

            'name_tree'     => $name,
            'slug_tree'     => Str::slug($name),

            "language_id"   => $language?->id ?? "1",
            "category_id"   => null,

            "parent_id"     => null,
            "created_by_id" => $user?->id ?? "1",
        ];
    }
}
