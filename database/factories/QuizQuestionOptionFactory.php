<?php
namespace Database\Factories;

use App\Helpers\SeederHelper;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<QuizQuestionOption>
 */
class QuizQuestionOptionFactory extends Factory
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

        $quizQuestion = QuizQuestion::with(['quiz','quiz.language'])
            ->inRandomOrder()
            ->first();

        $languageCode = $quizQuestion?->quiz?->language?->code ?? SeederHelper::LANGUAGE_EN_CODE;

        $quizzes = SeederHelper::quizSeederData($languageCode);

        $quizData = collect($quizzes)->firstWhere('name', $quizQuestion?->quiz?->name);

        if (! $quizData) {
            $quizData = collect($quizzes)->random();
        }

        $questionData = collect($quizData['questions'])
            ->firstWhere('question', $quizQuestion?->question);

        if (! $questionData) {
            $questionData = collect($quizData['questions'])->random();
        }

        $optionData = collect($questionData['options'])->random();

        return [
            'quiz_question_id' => $quizQuestion?->id ?? 1,
            'option'           => $optionData['option'],
            'is_correct'       => $optionData['is_correct'],
            'position'         => $this->faker->numberBetween(1, count($questionData['options'])),
            'created_by_id'    => $user?->id ?? 1,
        ];
    }
}
