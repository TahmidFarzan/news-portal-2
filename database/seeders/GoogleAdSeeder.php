<?php
namespace Database\Seeders;

use App\Helpers\SeederHelper;
use App\Helpers\GoogleAdHelper;
use App\Models\GoogleAd;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GoogleAdSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            GoogleAd::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='ad_sences'");
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            GoogleAd::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            GoogleAd::truncate();
        }

        GoogleAd::factory()->state([
            'name'      => "Section Top",
            'type'      => GoogleAdHelper::TYPE_SECTION,
            'position'  => GoogleAdHelper::POSITION_TOP,
            'slot_id'   => SeederHelper::GOOGLE_AD_ADSENSE_SLOT_ID,
            'client_id' => SeederHelper::GOOGLE_AD_ADSENSE_CLIENT_ID,
        ])->create();

        GoogleAd::factory()->state([
            'name'      => "Section Bottom",
            'type'      => GoogleAdHelper::TYPE_SECTION,
            'position'  => GoogleAdHelper::POSITION_BOTTOM,
            'slot_id'   => SeederHelper::GOOGLE_AD_ADSENSE_SLOT_ID,
            'client_id' => SeederHelper::GOOGLE_AD_ADSENSE_CLIENT_ID,
        ])->create();

        GoogleAd::factory()->state([
            'name'      => "Section Bottom",
            'type'      => GoogleAdHelper::TYPE_SECTION,
            'position'  => GoogleAdHelper::POSITION_BETWEEN,
            'slot_id'   => SeederHelper::GOOGLE_AD_ADSENSE_SLOT_ID,
            'client_id' => SeederHelper::GOOGLE_AD_ADSENSE_CLIENT_ID,
        ])->create();

        GoogleAd::factory()->state([
            'name'      => "Sidebar Top",
            'type'      => GoogleAdHelper::TYPE_SIDEBAR,
            'position'  => GoogleAdHelper::POSITION_TOP,
            'slot_id'   => SeederHelper::GOOGLE_AD_ADSENSE_SLOT_ID,
            'client_id' => SeederHelper::GOOGLE_AD_ADSENSE_CLIENT_ID,
        ])->create();

        GoogleAd::factory()->state([
            'name'      => "Sidebar Bottom",
            'type'      => GoogleAdHelper::TYPE_SIDEBAR,
            'position'  => GoogleAdHelper::POSITION_BETWEEN,
            'slot_id'   => SeederHelper::GOOGLE_AD_ADSENSE_SLOT_ID,
            'client_id' => SeederHelper::GOOGLE_AD_ADSENSE_CLIENT_ID,
        ])->create();

        GoogleAd::factory()->state([
            'name'      => "Sidebar Bottom",
            'type'      => GoogleAdHelper::TYPE_SIDEBAR,
            'position'  => GoogleAdHelper::POSITION_BOTTOM,
            'slot_id'   => SeederHelper::GOOGLE_AD_ADSENSE_SLOT_ID,
            'client_id' => SeederHelper::GOOGLE_AD_ADSENSE_CLIENT_ID,
        ])->create();
    }
}
