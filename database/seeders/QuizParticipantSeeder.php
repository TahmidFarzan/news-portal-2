<?php

namespace Database\Seeders;

use App\Models\QuizParticipant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuizParticipantSeeder extends Seeder
{
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            QuizParticipant::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='quiz_participants'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            QuizParticipant::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            QuizParticipant::truncate();
        }

        for ($i = 0; $i < 45; $i++) {
            QuizParticipant::factory()->create();
        }
    }
}
