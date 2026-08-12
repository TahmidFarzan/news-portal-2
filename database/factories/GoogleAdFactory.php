<?php

namespace Database\Factories;

use App\Helpers\GoogleAdHelper;
use App\Helpers\SeederHelper;
use App\Models\GoogleAd;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GoogleAdFactory extends Factory
{
    public function definition(): array
    {
        $user = User::where('is_super_admin', true)
            ->inRandomOrder()
            ->first();

        $type = $this->faker->randomElement([
            GoogleAdHelper::TYPE_SECTION,
            GoogleAdHelper::TYPE_SIDEBAR,
            GoogleAdHelper::TYPE_POPUP_HOME_PAGE,
            GoogleAdHelper::TYPE_POPUP_LATEST_PAGE,
            GoogleAdHelper::TYPE_POPUP_SEARCH_PAGE,
            GoogleAdHelper::TYPE_POPUP_VIDEO_PAGE,
            GoogleAdHelper::TYPE_POPUP_IMAGE_GALLERY_PAGE,
            GoogleAdHelper::TYPE_POPUP_CATEGORY_PAGE,
            GoogleAdHelper::TYPE_POPUP_TAG_PAGE,
            GoogleAdHelper::TYPE_POPUP_EVENT_PAGE,
            GoogleAdHelper::TYPE_POPUP_LOCATION_PAGE,
            GoogleAdHelper::TYPE_POPUP_NEWS_DETAILS_PAGE,
        ]);

        $isPopup = str_starts_with($type, 'Pop Up');

        $testAd = $this->faker->randomElement(
            SeederHelper::GOOGLE_AD_MANAGER_TEST_ADS
        );

        return [
            'name' => $this->faker->name(),
            'type' => $type,
            'position' => $isPopup
                ? null
                : $this->faker->randomElement([
                    GoogleAdHelper::POSITION_TOP,
                    GoogleAdHelper::POSITION_BETWEEN,
                    GoogleAdHelper::POSITION_BOTTOM,
                ]),
            'ad_unit_code' => $testAd['ad_unit_code'],
            'gpt_slot_id' => 'div-gpt-ad-' . Str::uuid(),
            'ad_sizes' => $testAd['ad_sizes'],
            'created_by_id' => $user?->id ?? 1,
        ];
    }
}
