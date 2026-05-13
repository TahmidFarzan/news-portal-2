<?php
namespace Database\Seeders;

use App\Helpers\NewsHelper;
use App\Models\NewsType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsTypeSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            NewsType::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='user_roles'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            NewsType::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            NewsType::truncate();
        }

        foreach (NewsHelper::newsTypes() as $newsType) {
            NewsType::factory()->state([
                'name' => $newsType->id,
            ])->create();
        }
    }
}
