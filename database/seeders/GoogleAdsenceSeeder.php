<?php
namespace Database\Seeders;

use App\Helpers\GoogleAdsenceHelper;
use App\Models\GoogleAdsence;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoogleAdsenceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            GoogleAdsence::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='ad_sences'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            GoogleAdsence::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            GoogleAdsence::truncate();
        }

        GoogleAdsence::factory()->state([
            'name'      => "Section Top",
            'type'      => GoogleAdsenceHelper::TYPE_SECTION,
            'position'  => GoogleAdsenceHelper::POSITION_TOP,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();

        GoogleAdsence::factory()->state([
            'name'      => "Section Bottom",
            'type'      => GoogleAdsenceHelper::TYPE_SECTION,
            'position'  => GoogleAdsenceHelper::POSITION_BOTTOM,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();

        GoogleAdsence::factory()->state([
            'name'      => "Section Bottom",
            'type'      => GoogleAdsenceHelper::TYPE_SECTION,
            'position'  => GoogleAdsenceHelper::POSITION_BETWEEN,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();

        GoogleAdsence::factory()->state([
            'name'      => "Sidebar Top",
            'type'      => GoogleAdsenceHelper::TYPE_SIDEBAR,
            'position'  => GoogleAdsenceHelper::POSITION_TOP,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();

        GoogleAdsence::factory()->state([
            'name'      => "Sidebar Bottom",
            'type'      => GoogleAdsenceHelper::TYPE_SIDEBAR,
            'position'  => GoogleAdsenceHelper::POSITION_BETWEEN,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();

        GoogleAdsence::factory()->state([
            'name'      => "Sidebar Bottom",
            'type'      => GoogleAdsenceHelper::TYPE_SIDEBAR,
            'position'  => GoogleAdsenceHelper::POSITION_BOTTOM,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();
    }
}
