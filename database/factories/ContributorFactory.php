<?php
namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Models\Contributor;
use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Contributor>
 */
class ContributorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::where("is_super_admin", true)->inRandomOrder()->first();

        $language = Language::where("code", SeederHelper::LANGUAGE_EN_CODE)->first() ?? null;

        $name  = $this->faker->name();
        $brief = $this->faker->sentence();
        return [
            'name'            => $name,
            'brief'           => $brief,
            'profile_details' => null,

            'seo_title'       => $name,
            'seo_brief'       => $brief,

            "language_id"     => $language?->id ?? "1",

            "created_by_id"   => $user?->id ?? "1",
        ];
    }
}
