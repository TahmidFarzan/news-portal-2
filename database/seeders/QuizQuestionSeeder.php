<?php
namespace Database\Seeders;

use App\Helpers\SeederHelper;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizQuestionSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');

            QuizQuestion::query()->delete();

            DB::statement("DELETE FROM sqlite_sequence WHERE name='quiz_questions'");

            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            QuizQuestion::truncate();

            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            QuizQuestion::truncate();
        }

        $user = User::where('is_super_admin', true)->inRandomOrder()->first();

        Quiz::with('language')
            ->chunk(100, function ($quizzes) use ($user) {

                foreach ($quizzes as $quiz) {

                    $languageCode = $quiz->language?->code;

                    $quizSeederData = SeederHelper::quizSeederData($languageCode);

                    if (empty($quizSeederData)) {
                        continue;
                    }

                    $quizData = collect($quizSeederData)
                        ->firstWhere('name', $quiz->name);

                    if (! $quizData) {
                        continue;
                    }

                    foreach ($quizData['questions'] as $index => $questionData) {

                        QuizQuestion::factory()
                            ->state([
                                'quiz_id'       => $quiz->id,
                                'question'      => $questionData['question'],
                                'answer_type'   => $questionData['answer_type'],
                                'point'         => $questionData['point'] ?? 1,
                                'position'      => $index + 1,
                                'created_by_id' => $user?->id ?? 1,
                            ])
                            ->create();
                    }
                }
            });
    }
}
