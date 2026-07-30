<?php
namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestion>
 */
class QuizQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $user = User::where('is_super_admin', true)
            ->inRandomOrder()
            ->first();

        $quiz = Quiz::with('language')
            ->inRandomOrder()
            ->first();

        $languageCode = $quiz?->language?->code ?? SeederHelper::LANGUAGE_EN_CODE;

        $quizzes = SeederHelper::quizSeederData($languageCode);

        $quizData = collect($quizzes)
            ->firstWhere('name', $quiz?->name);

        if (! $quizData) {
            $quizData = collect($quizzes)->random();
        }

        $questionData = collect($quizData['questions'])->random();

        return [
            'quiz_id'       => $quiz?->id ?? 1,
            'question'      => $questionData['question'],
            'answer_type'   => $questionData['answer_type'],
            'point'         => $questionData['point'] ?? 1,
            'position'      => $this->faker->numberBetween(1, count($quizData['questions'])),
            'created_by_id' => $user?->id ?? 1,
        ];
    }
}
