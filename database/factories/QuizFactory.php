<?php
namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Quiz>
 */
class QuizFactory extends Factory
{
    public function definition(): array
    {
        $user = User::where('is_super_admin', true)
            ->inRandomOrder()
            ->first();

        $language = Language::where(
            'code',
            SeederHelper::LANGUAGE_EN_CODE
        )->first();

        $quizzes = SeederHelper::quizSeederData(
            SeederHelper::LANGUAGE_EN_CODE
        );

        $quiz = collect($quizzes)->random();

        $startDate = now()->startOfDay();
        $endDate   = now()->addDays(rand(1, 30))->startOfDay();

        return [
            'name'              => $quiz['name'],
            'brief'             => $quiz['brief'],
            'language_id'       => $language?->id ?? 1,
            'start_date'        => $startDate,
            'end_date'          => $endDate,
            'is_active'        => $this->faker->boolean(),
            'show_bellow_event' => $this->faker->boolean(),
            'created_by_id'     => $user?->id ?? 1,
        ];
    }
}
