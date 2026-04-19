<?php
namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            UserRole::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='user_roles'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            UserRole::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            UserRole::truncate();
        }

        foreach (["Admin", "News Desk"] as $role) {
            UserRole::factory()->state([
                'name' => $role,
            ])->create();
        }
    }
}
