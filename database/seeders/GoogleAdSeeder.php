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
                'name' => GoogleAdHelper::TYPE_SECTION. GoogleAdHelper::POSITION_TOP,
                'type' => GoogleAdHelper::TYPE_SECTION,
                'position' => GoogleAdHelper::POSITION_TOP,
            ],
            [
                'name' => GoogleAdHelper::TYPE_SECTION. GoogleAdHelper::POSITION_BETWEEN,
                'type' => GoogleAdHelper::TYPE_SECTION,
                'position' => GoogleAdHelper::POSITION_BETWEEN,
            ],
            [
                'name' => GoogleAdHelper::TYPE_SECTION. GoogleAdHelper::POSITION_BOTTOM,
                'type' => GoogleAdHelper::TYPE_SECTION,
                'position' => GoogleAdHelper::POSITION_BOTTOM,
            ],
            [
                'name' => GoogleAdHelper::TYPE_SIDEBAR. GoogleAdHelper::POSITION_TOP,
                'type' => GoogleAdHelper::TYPE_SIDEBAR,
                'position' => GoogleAdHelper::POSITION_TOP,
            ],
            [
                'name' => GoogleAdHelper::TYPE_SIDEBAR. GoogleAdHelper::POSITION_BETWEEN,
                'type' => GoogleAdHelper::TYPE_SIDEBAR,
                'position' => GoogleAdHelper::POSITION_BETWEEN,
            ],
            [
                'name' => GoogleAdHelper::TYPE_SIDEBAR. GoogleAdHelper::POSITION_BOTTOM,
                'type' => GoogleAdHelper::TYPE_SIDEBAR,
                'position' => GoogleAdHelper::POSITION_BOTTOM,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_HOME_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_HOME_PAGE,
                'position' => null,
            ],
            [
                'name' => 'Pop Up - Latest Page',
                'type' => GoogleAdHelper::TYPE_POPUP_LATEST_PAGE,
                'position' => null,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_SEARCH_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_SEARCH_PAGE,
                'position' => null,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_VIDEO_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_VIDEO_PAGE,
                'position' => null,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_IMAGE_GALLERY_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_IMAGE_GALLERY_PAGE,
                'position' => null,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_CATEGORY_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_CATEGORY_PAGE,
                'position' => null,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_TAG_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_TAG_PAGE,
                'position' => null,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_EVENT_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_EVENT_PAGE,
                'position' => null,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_LOCATION_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_LOCATION_PAGE,
                'position' => null,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_NEWS_DETAILS_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_NEWS_DETAILS_PAGE,
                'position' => null,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_CONTACT_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_CONTACT_PAGE,
                'position' => null,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_ABOUT_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_ABOUT_PAGE,
                'position' => null,
            ],
            [
                'name' => GoogleAdHelper::TYPE_POPUP_OTHER_PAGE,
                'type' => GoogleAdHelper::TYPE_POPUP_OTHER_PAGE,
                'position' => null,
            ],
        ];

        $testAds = SeederHelper::GOOGLE_AD_MANAGER_TEST_ADS;

        $popupTestAd = collect($testAds)->firstWhere(
            'name',
            GoogleAdHelper::POPUP_LABEL
        );

        $regularTestAds = collect($testAds)
            ->reject(fn($testAd) => $testAd['name'] === GoogleAdHelper::POPUP_LABEL)
            ->values();

        foreach ($ads as $index => $ad) {
            $isPopup = str_starts_with($ad['type'], GoogleAdHelper::POPUP_LABEL);

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
                'gpt_slot_id' => ($testAd['gpt_slot_id'] ?? "div-gpt-ad").'-' . Str::uuid(),
                'ad_sizes' => $testAd['ad_sizes'],
            ])->create();
        }
    }
}
