<?php
namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            Language::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='languages'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            Language::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            Language::truncate();
        }

        $languages = collect([
            (object) ['name' => 'Bangla', 'code' => 'bn_bd'],
            (object) ['name' => 'English', 'code' => 'en_us'],
        ]);

        foreach ($languages as $language) {
            Language::factory()->state([
                'name' => $language->name,
                'code' => $language->code,
            ])->create();
        }
    }
}
