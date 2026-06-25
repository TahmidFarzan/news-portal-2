<?php
namespace Database\Factories;

use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Language>
 */
class LanguageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::where("is_super_admin", true)->inRandomOrder()->first();

        return [
            'name'          => $this->faker->name(),
            'code'          => Str::snake(Str::lower($this->faker->unique()->lexify('??'))),
            'locale'        => Str::snake(Str::lower($this->faker->unique()->lexify('??'))),
            'brief'         => $this->faker->sentence(),
            "created_by_id" => $user?->id ?? "1",
        ];
    }
}
