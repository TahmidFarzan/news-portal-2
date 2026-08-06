<?php

namespace Database\Seeders;


use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizResult;
use App\Models\QuizParticipant;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuizResultSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            QuizResult::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='quiz_results'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            QuizResult::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            QuizResult::truncate();
        }

        $quizzes = Quiz::orderBy("id", "desc")->get();

        foreach ($quizzes as $quiz) {

            QuizParticipant::select('id')->orderByDesc('id')->chunk(100, function ($participants) use ($quiz) {
                $quizQuestions = QuizQuestion::where("quiz_id",$quiz->id)->orderBy("id", "desc")->get();

                foreach ($participants as $participant) {

                    $totalPoint = 0;

                    foreach ($quizQuestions as $question) {

                        $correct = fake()->boolean(70);

                        if ($correct){
                            $totalPoint += $question->point;
                        }
                    }

                    QuizResult::factory()->state([
                        'quiz_id' => $quiz->id,
                        'quiz_participant_id' => $participant->id,

                        'duration' => fake()->numberBetween(45, 345),

                        'total_point' => $totalPoint,
                    ])->create();
                }
            });
        }
    }
}
