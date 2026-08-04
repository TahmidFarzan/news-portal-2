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

        ];
    }
}
