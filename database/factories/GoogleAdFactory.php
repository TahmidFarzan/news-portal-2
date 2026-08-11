<?php
namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Helpers\GoogleAdHelper;
use App\Models\GoogleAd;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GoogleAd>
 */
class GoogleAdFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::where("is_super_admin", true)->inRandomOrder()->first();

        $name = $this->faker->name();

        return [
            'name'          => $name,
            'type'          => GoogleAdHelper::TYPE_SECTION,
            'position'      => GoogleAdHelper::POSITION_TOP,
            "created_by_id" => $user?->id ?? "1",
            'slot_id'       => SeederHelper::GOOGLE_AD_ADSENSE_SLOT_ID,
            'client_id'     => SeederHelper::GOOGLE_AD_ADSENSE_CLIENT_ID,
        ];
    }
}
