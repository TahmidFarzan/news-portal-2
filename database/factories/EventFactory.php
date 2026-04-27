<?php
namespace Database\Factories;

use App\Models\Language;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    public function definition(): array
    {
        $adminUserRole = UserRole::where("name", "Admin")->inRandomOrder()->first();
        $user          = User::inRandomOrder()->where("user_role_id", $adminUserRole->id)->first() ?? null;
        $language      = Language::where("code", "en_us")->first() ?? null;

        $name    = $this->faker->name();
        $details = $this->faker->sentence();

        return [
            'name'          => $name,
            'details'       => $details,

            'seo_title'     => $name,
            'seo_brief'     => $details,

            "is_current"    => false,

            "language_id"   => $language?->id ?? "1",

            "created_by_id" => $user?->id ?? "1",
        ];
    }
}
