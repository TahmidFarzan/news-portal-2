<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (env("DB_CONNECTION") === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            User::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='users'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (env("DB_CONNECTION") === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            User::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(env("DB_CONNECTION"), ['pgsql', 'sqlsrv'])) {
            User::truncate();
        }

        // Seeder
        $adminUserRole = UserRole::where("name", "Admin")->first();
        User::factory()->state([
            'name'              => "Default Admin",
            'email'             => "admin@gmail.com",
            'email_verified_at' => now(),
            "is_default"        => true,
            "user_role_id"      => $adminUserRole?->id,
            "created_by_id"     => null,
            'gender'            => 'Male',
            'religion'          => 'Islam',
            'marital_status'    => 'Single',
        ])->create();

        for ($i = 0; $i < 15; $i++) {
            User::factory()->create();
        }
    }
}
