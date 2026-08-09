<?php
namespace Database\Seeders;

use App\Helpers\GoogleAdsenseHelper;
use App\Models\GoogleAdsense;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoogleAdsenseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            GoogleAdsense::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='ad_sences'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            GoogleAdsense::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            GoogleAdsense::truncate();
        }

        GoogleAdsense::factory()->state([
            'name'      => "Section Top",
            'type'      => GoogleAdsenseHelper::TYPE_SECTION,
            'position'  => GoogleAdsenseHelper::POSITION_TOP,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();

        GoogleAdsense::factory()->state([
            'name'      => "Section Bottom",
            'type'      => GoogleAdsenseHelper::TYPE_SECTION,
            'position'  => GoogleAdsenseHelper::POSITION_BOTTOM,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();

        GoogleAdsense::factory()->state([
            'name'      => "Section Bottom",
            'type'      => GoogleAdsenseHelper::TYPE_SECTION,
            'position'  => GoogleAdsenseHelper::POSITION_BETWEEN,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();

        GoogleAdsense::factory()->state([
            'name'      => "Sidebar Top",
            'type'      => GoogleAdsenseHelper::TYPE_SIDEBAR,
            'position'  => GoogleAdsenseHelper::POSITION_TOP,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();

        GoogleAdsense::factory()->state([
            'name'      => "Sidebar Bottom",
            'type'      => GoogleAdsenseHelper::TYPE_SIDEBAR,
            'position'  => GoogleAdsenseHelper::POSITION_BETWEEN,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();

        GoogleAdsense::factory()->state([
            'name'      => "Sidebar Bottom",
            'type'      => GoogleAdsenseHelper::TYPE_SIDEBAR,
            'position'  => GoogleAdsenseHelper::POSITION_BOTTOM,
            'slot_id'   => config("util.google-ad.test_ad_slot"),
            'client_id' => config("util.google-ad.test_client_id"),
        ])->create();
    }
}
