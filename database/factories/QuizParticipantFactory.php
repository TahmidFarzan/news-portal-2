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
        $hasMobile = fake()->boolean(70);
        $hasEmail = fake()->boolean(70);

        if(!$hasEmail && !$hasMobile){
            $hasEmail = true;
        }

        return [
            'name' => $this->faker->name(),
            'mobile' => $hasMobile ? $this->faker->unique()->numerify('01#########') : null,
            'email' => $hasEmail ? $this->faker->unique()->safeEmail() : null,
            'address' => $this->faker->optional()->address(),
        ];
    }
}
