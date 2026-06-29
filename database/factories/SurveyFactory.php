<?php
namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Models\Language;
use App\Models\Survey;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Survey>
 */
class SurveyFactory extends Factory
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

        $name     = $this->faker->name();
        $brief    = $this->faker->sentence();
        $isActive = $this->faker->boolean(50);

        return [
            'name'          => $name,
            'brief'         => $brief,
            'date'          => now(),
            "language_id"   => $language?->id ?? "1",
            "created_by_id" => $user?->id ?? "1",
            "is_active"     => $isActive,
        ];
    }
}
