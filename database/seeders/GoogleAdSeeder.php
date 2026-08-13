<?php

namespace Database\Seeders;

use App\Helpers\GoogleAdHelper;
use App\Helpers\SeederHelper;
use App\Models\GoogleAd;
use App\Models\User;
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

        $user = User::query()
            ->where('is_super_admin', true)
            ->inRandomOrder()
            ->first();

        $pages = GoogleAdHelper::pages()
            ->pluck('id')
            ->values()
            ->all();

        $testAds = collect(SeederHelper::GOOGLE_AD_MANAGER_TEST_ADS);

        $sectionTestAds = $testAds
            ->where('type', GoogleAdHelper::TYPE_SECTION)
            ->values();

        $sidebarTestAds = $testAds
            ->where('type', GoogleAdHelper::TYPE_SIDEBAR)
            ->values();

        $popupTestAd = $testAds
            ->firstWhere('type', GoogleAdHelper::TYPE_POPUP);

        foreach ($pages as $page) {
            $sectionPlacements = $page === GoogleAdHelper::PAGE_HOME
                ? [
                    GoogleAdHelper::PLACEMENT_1,
                    GoogleAdHelper::PLACEMENT_2,
                    GoogleAdHelper::PLACEMENT_3,
                    GoogleAdHelper::PLACEMENT_4,
                    GoogleAdHelper::PLACEMENT_5,
                    GoogleAdHelper::PLACEMENT_6,
                ]
                : [
                    GoogleAdHelper::PLACEMENT_1,
                    GoogleAdHelper::PLACEMENT_2,
                    GoogleAdHelper::PLACEMENT_3,
                ];

            foreach ($sectionPlacements as $index => $placement) {
                $testAd = $sectionTestAds[$index % $sectionTestAds->count()];

                GoogleAd::create([
                    'name' => "{$page} Section {$placement}",
                    'type' => GoogleAdHelper::TYPE_SECTION,
                    'page' => $page,
                    'placement' => $placement,
                    'ad_unit_code' => $testAd['ad_unit_code'],
                    'gpt_slot_id' => $this->generateGptSlotId(
                        $page,
                        GoogleAdHelper::TYPE_SECTION,
                        $placement
                    ),
                    'ad_sizes' => $testAd['ad_sizes'],
                    'created_by_id' => $user?->id ?? 1,
                ]);
            }

            $sidebarPlacements = $page === GoogleAdHelper::PAGE_HOME
                ? [
                    GoogleAdHelper::PLACEMENT_2,
                ]
                : [
                    GoogleAdHelper::PLACEMENT_1,
                    GoogleAdHelper::PLACEMENT_2,
                ];

            foreach ($sidebarPlacements as $index => $placement) {
                $testAd = $sidebarTestAds[$index % $sidebarTestAds->count()];

                GoogleAd::create([
                    'name' => "{$page} Sidebar {$placement}",
                    'type' => GoogleAdHelper::TYPE_SIDEBAR,
                    'page' => $page,
                    'placement' => $placement,
                    'ad_unit_code' => $testAd['ad_unit_code'],
                    'gpt_slot_id' => $this->generateGptSlotId(
                        $page,
                        GoogleAdHelper::TYPE_SIDEBAR,
                        $placement
                    ),
                    'ad_sizes' => $testAd['ad_sizes'],
                    'created_by_id' => $user?->id ?? 1,
                ]);
            }

            GoogleAd::create([
                'name' => "{$page} Pop Up",
                'type' => GoogleAdHelper::TYPE_POPUP,
                'page' => $page,
                'placement' => null,
                'ad_unit_code' => $popupTestAd['ad_unit_code'],
                'gpt_slot_id' => $this->generateGptSlotId(
                    $page,
                    GoogleAdHelper::TYPE_POPUP
                ),
                'ad_sizes' => $popupTestAd['ad_sizes'],
                'created_by_id' => $user?->id ?? 1,
            ]);
        }
    }

    private function generateGptSlotId(
        string $page,
        string $type,
        ?string $placement = null
    ): string {
        $parts = [
            'div-gpt-ad',
            $this->slug($page),
            $this->slug($type),
        ];

        if ($placement !== null) {
            $parts[] = $placement;
        }

        $parts[] = Str::uuid();

        return implode('-', $parts);
    }

    private function slug(string $value): string
    {
        return Str::slug($value);
    }
}
