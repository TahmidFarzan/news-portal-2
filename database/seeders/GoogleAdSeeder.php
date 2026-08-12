<?php

namespace Database\Seeders;

use App\Helpers\GoogleAdHelper;
use App\Helpers\SeederHelper;
use App\Models\GoogleAd;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GoogleAdSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            GoogleAd::query()->delete();
            DB::statement("DELETE FROM sqlite_sequence WHERE name='google_ads'");
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

        $ads = [
            [
                'name' => 'Section Top',
                'type' => GoogleAdHelper::TYPE_SECTION,
                'position' => GoogleAdHelper::POSITION_TOP,
            ],
            [
                'name' => 'Section Between',
                'type' => GoogleAdHelper::TYPE_SECTION,
                'position' => GoogleAdHelper::POSITION_BETWEEN,
            ],
            [
                'name' => 'Section Bottom',
                'type' => GoogleAdHelper::TYPE_SECTION,
                'position' => GoogleAdHelper::POSITION_BOTTOM,
            ],
            [
                'name' => 'Sidebar Top',
                'type' => GoogleAdHelper::TYPE_SIDEBAR,
                'position' => GoogleAdHelper::POSITION_TOP,
            ],
            [
                'name' => 'Sidebar Between',
                'type' => GoogleAdHelper::TYPE_SIDEBAR,
                'position' => GoogleAdHelper::POSITION_BETWEEN,
            ],
            [
                'name' => 'Sidebar Bottom',
                'type' => GoogleAdHelper::TYPE_SIDEBAR,
                'position' => GoogleAdHelper::POSITION_BOTTOM,
            ],
            [
                'name' => 'Pop Up - Home Page',
                'type' => GoogleAdHelper::TYPE_POPUP_HOME_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - Latest Page',
                'type' => GoogleAdHelper::TYPE_POPUP_LATEST_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - Search Page',
                'type' => GoogleAdHelper::TYPE_POPUP_SEARCH_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - Video Page',
                'type' => GoogleAdHelper::TYPE_POPUP_VIDEO_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - Image Gallery Page',
                'type' => GoogleAdHelper::TYPE_POPUP_IMAGE_GALLERY_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - Category Page',
                'type' => GoogleAdHelper::TYPE_POPUP_CATEGORY_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - Tag Page',
                'type' => GoogleAdHelper::TYPE_POPUP_TAG_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - Event Page',
                'type' => GoogleAdHelper::TYPE_POPUP_EVENT_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - Location Page',
                'type' => GoogleAdHelper::TYPE_POPUP_LOCATION_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - News Details Page',
                'type' => GoogleAdHelper::TYPE_POPUP_NEWS_DETAILS_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - Contact Page',
                'type' => GoogleAdHelper::TYPE_POPUP_CONTACT_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - About Page',
                'type' => GoogleAdHelper::TYPE_POPUP_ABOUT_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - Other Page',
                'type' => GoogleAdHelper::TYPE_POPUP_OTHER_PAGE,
                'position' => null,
            ],
        ];

        $testAds = SeederHelper::GOOGLE_AD_MANAGER_TEST_ADS;

        $popupTestAd = collect($testAds)->firstWhere(
            'name',
            'Popup'
        );

        $regularTestAds = collect($testAds)
            ->reject(fn($testAd) => $testAd['name'] === 'Popup')
            ->values();

        foreach ($ads as $index => $ad) {
            $isPopup = str_starts_with($ad['type'], 'Pop Up');

            if ($isPopup) {
                $testAd = $popupTestAd;
            } else {
                $testAd = $regularTestAds[$index % $regularTestAds->count()];
            }

            GoogleAd::factory()->state([
                'name' => $ad['name'],
                'type' => $ad['type'],
                'position' => $ad['position'],
                'ad_unit_code' => $testAd['ad_unit_code'],
                'gpt_slot_id' => 'div-gpt-ad-' . Str::uuid(),
                'ad_sizes' => $testAd['ad_sizes'],
            ])->create();
        }
    }
}
