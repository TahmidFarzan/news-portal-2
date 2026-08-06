<?php

namespace Database\Factories;

use App\Models\QuizParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizParticipant>
 */
class QuizParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $hasPhone = fake()->boolean(70);
        $hasEmail = fake()->boolean(70);

        return [
            'name' => $this->faker->name(),
            'phone' => $hasPhone ? $this->faker->unique()->numerify('01#########') : null,
            'email' => $hasEmail ? $this->faker->unique()->safeEmail() : null,
            'address' => $this->faker->optional()->address(),
        ];
    }
}
