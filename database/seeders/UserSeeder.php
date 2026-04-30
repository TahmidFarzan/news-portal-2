<?php
namespace Database\Seeders;

use App\Helpers\MediaHelper;
use App\Models\User;
use App\Models\UserRole;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Helpers\UserHelper;

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
        $adminUserRole = UserRole::where("name", UserHelper::USER_ROLE_ADMIN)->first();
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

        for ($i = 0; $i < 25; $i++) {
            User::factory()->create();
        }

        $profileImageUrl = MediaHelper::defaultAuthImage("1:1", "user");
        if ($profileImageUrl) {
            $users = User::orderBy("id", "desc")->get();
            foreach ($users as $user) {
                try {
                    $headers = get_headers($profileImageUrl, 1);
                    if (strpos($headers[0], '200') !== false) {
                        $profileImageExtension = pathinfo($profileImageUrl, PATHINFO_EXTENSION);
                        $profileImageExtension = in_array($profileImageExtension, ["png", "jpg", "jpeg"]) ? $profileImageExtension : "png";
                        $profileImageFileName  = MediaHelper::generateMediaName($user->name, $profileImageExtension, 200);
                        $user->addMediaFromUrl($profileImageUrl)
                            ->usingName($user->name)
                            ->usingFileName($profileImageFileName)
                            ->withCustomProperties(['caption' => $user->name, 'alt' => $user->name, "role" => MediaHelper::MEDIA_ROLE_PROFILE_IMAGE])
                            ->toMediaCollection($user->media_collection_name);
                    } else {
                        Log::info("Image not accessable user: {$user->name}");
                    }
                } catch (Exception $ex) {
                    Log::info("Failed to fetch Image for user {$user->name}: {$ex->getMessage()}");
                }
            }
        }
    }
}
