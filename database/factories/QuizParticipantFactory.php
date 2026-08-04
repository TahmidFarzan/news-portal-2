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
            'ip' => $this->faker->ipv4(),
            'device_info' => [
                'browser' => fake()->randomElement(['Chrome', 'Firefox', 'Edge', 'Safari']),
                'browser_version' => fake()->numberBetween(90, 140),
                'os' => fake()->randomElement(['Windows', 'macOS', 'Linux', 'Android', 'iOS']),
                'device_type' => fake()->randomElement(['Desktop', 'Laptop', 'Mobile', 'Tablet']),
                'platform' => fake()->randomElement(['x64', 'ARM64']),
                'user_agent' => fake()->userAgent(),
            ],
        ];
    }
}
