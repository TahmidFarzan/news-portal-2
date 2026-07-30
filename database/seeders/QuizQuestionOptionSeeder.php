<?php

namespace Database\Seeders;

use App\Helpers\SeederHelper;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizQuestionOptionSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            QuizQuestionOption::query()->delete();

            DB::statement("DELETE FROM sqlite_sequence WHERE name='quiz_question_options'");

            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            QuizQuestionOption::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            QuizQuestionOption::truncate();
        }

        $user = User::where('is_super_admin', true)
            ->inRandomOrder()
            ->first();

        QuizQuestion::with(['quiz', 'quiz.language'])
            ->chunk(100, function ($quizQuestions) use ($user) {

                foreach ($quizQuestions as $quizQuestion) {

                    $languageCode = $quizQuestion->quiz?->language?->code;

                    $quizSeederData = SeederHelper::quizSeederData($languageCode);

                    if (empty($quizSeederData)) {
                        continue;
                    }

                    $quizData = collect($quizSeederData)
                        ->firstWhere('name', $quizQuestion->quiz->name);

                    if (! $quizData) {
                        continue;
                    }

                    $questionData = collect($quizData['questions'])
                        ->firstWhere('question', $quizQuestion->question);

                    if (! $questionData) {
                        continue;
                    }

                    foreach ($questionData['options'] as $index => $optionData) {

                        QuizQuestionOption::factory()
                            ->state([
                                'quiz_question_id' => $quizQuestion->id,
                                'option'           => $optionData['option'],
                                'is_correct'       => $optionData['is_correct'],
                                'position'         => $index + 1,
                                'created_by_id'    => $user?->id ?? 1,
                            ])
                            ->create();
                    }
                }
            });
    }
}
