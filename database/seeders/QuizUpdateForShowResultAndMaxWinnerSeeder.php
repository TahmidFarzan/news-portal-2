<?php

namespace Database\Seeders;

use App\Models\Quiz;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QuizUpdateForShowResultAndMaxWinnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Quiz::query()->each(function (Quiz $quiz) {
            $quiz->update([
                'enable_result' => true,
                'max_winner' => random_int(7, 13),
            ]);
        });
    }
}
