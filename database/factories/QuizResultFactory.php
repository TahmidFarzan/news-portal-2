<?php

namespace Database\Factories;

use App\Models\QuizResult;
use App\Models\Quiz;
use App\Models\QuizParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizResult>
 */
class QuizResultFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::query()->inRandomOrder()->where("is_active", true)->value('id'),
            'quiz_participant_id' => QuizParticipant::query()->inRandomOrder()->value('id'),
            'duration' => fake()->numberBetween(10000, 300000),
            'total_point' => 0,
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
