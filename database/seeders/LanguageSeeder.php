<?php
namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Helpers\SeederHelper;

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

        $languagesFromStaticData = collect([
            (object) ['name' => 'English', 'code' => SeederHelper::LANGUAGE_EN_CODE, 'locale' => SeederHelper::LANGUAGE_EN_CODE."_US"],
            (object) ['name' => 'Bangla', 'code' => SeederHelper::LANGUAGE_BN_CODE, 'locale' => SeederHelper::LANGUAGE_BN_CODE."_BD"],
        ]);

        foreach ($languagesFromStaticData as $language) {
            Language::factory()->state([
                'name' => $language->name,
                'code' => $language->code,
                'locale' => $language->locale,
            ])->create();
        }
    }
}
