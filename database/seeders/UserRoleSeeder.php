<?php
namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Helpers\UserHelper;

class UserRoleSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            UserRole::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='user_roles'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            UserRole::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            UserRole::truncate();
        }

        foreach (UserHelper::userRoles() as $userRole) {
            UserRole::factory()->state([
                'name' => $userRole->id,
            ])->create();
        }
    }
}
