<?php
namespace Database\Seeders;

use App\Helpers\MenuHelper;
use App\Models\MenuType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MenuTypeSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            MenuType::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='menu_types'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            MenuType::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            MenuType::truncate();
        }

        foreach (MenuHelper::menuTypes() as $menuType) {
            MenuType::factory()->state([
                'name' => $menuType->id,
            ])->create();
        }
    }
}
