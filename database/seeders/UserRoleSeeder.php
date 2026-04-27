<?php
namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Helpers\SystemHelper;

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

        foreach ([SystemHelper::USER_ROLE_ADMIN , SystemHelper::USER_ROLE_NEWS_DESK] as $role) {
            UserRole::factory()->state([
                'name' => $role,
            ])->create();
        }
    }
}
