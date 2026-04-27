<?php
namespace Database\Seeders;

use App\Helpers\SystemHelper;
use App\Models\NewsUserType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsUserTypeSeeder extends Seeder
{
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            NewsUserType::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='news_user_types'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            NewsUserType::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            NewsUserType::truncate();
        }

        foreach ([SystemHelper::NEWS_USER_TYPE_AUTHOR, SystemHelper::NEWS_USER_TYPE_SPICIAL_AUTHOR, SystemHelper::NEWS_USER_TYPE_EDITOR, SystemHelper::NEWS_USER_TYPE_SUB_EDITOR] as $newsUserType) {
            NewsUserType::factory()->state([
                'name' => $newsUserType,
            ])->create();
        }
    }
}
