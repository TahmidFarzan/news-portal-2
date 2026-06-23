<?php
namespace Database\Factories;

use App\Helpers\UserHelper;
use App\Helpers\GoogleAdsenceHelper;
use App\Models\GoogleAdsence;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GoogleAdsence>
 */
class GoogleAdsenceFactory extends Factory
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

        $name = $this->faker->name();

        return [
            'name'          => $name,
            'type'          => GoogleAdsenceHelper::TYPE_SECTION,
            'position'          => GoogleAdsenceHelper::POSITION_TOP,
            "created_by_id" => $user?->id ?? "1",
            'slot_id'      => config("util.google-ad.test_ad_slot"),
            'client_id'      => config("util.google-ad.test_client_id"),
        ];
    }
}
