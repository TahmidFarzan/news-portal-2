<?php

namespace Database\Factories;

use App\Helpers\GoogleAdHelper;
use App\Helpers\SeederHelper;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GoogleAdFactory extends Factory
{
    public function definition(): array
    {
        $user = User::query()
            ->where('is_super_admin', true)
            ->inRandomOrder()
            ->first();

        $page = $this->faker->randomElement(
            GoogleAdHelper::pages()->pluck('id')->all()
        );

        $type = $this->faker->randomElement([
            GoogleAdHelper::TYPE_SECTION,
            GoogleAdHelper::TYPE_SIDEBAR,
            GoogleAdHelper::TYPE_POPUP,
        ]);

        $placement = $this->placement($page, $type);

        $testAd = $this->testAd($type);

        return [
            'name' => $this->name($page, $type, $placement),
            'type' => $type,
            'page' => $page,
            'placement' => $placement,
            'ad_unit_code' => $testAd['ad_unit_code'],
            'gpt_slot_id' => $this->gptSlotId($page, $type, $placement),
            'ad_sizes' => $testAd['ad_sizes'],
            'created_by_id' => $user?->id ?? 1,
        ];
    }

    private function placement(string $page, string $type): ?string
    {
        if ($type === GoogleAdHelper::TYPE_POPUP) {
            return null;
        }

        if ($type === GoogleAdHelper::TYPE_SIDEBAR) {
            $placements = $page === GoogleAdHelper::PAGE_HOME
                ? [
                    GoogleAdHelper::PLACEMENT_2,
                ]
                : [
                    GoogleAdHelper::PLACEMENT_1,
                    GoogleAdHelper::PLACEMENT_2,
                ];

            return $this->faker->randomElement($placements);
        }

        $placements = $page === GoogleAdHelper::PAGE_HOME
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

        return $this->faker->randomElement($placements);
    }

    private function testAd(string $type): array
    {
        return collect(SeederHelper::GOOGLE_AD_MANAGER_TEST_ADS)
            ->where('type', $type)
            ->random();
    }

    private function name(
        string $page,
        string $type,
        ?string $placement
    ): string {
        return trim(
            "{$page} {$type}" .
                ($placement !== null ? " {$placement}" : '')
        );
    }

    private function gptSlotId(
        string $page,
        string $type,
        ?string $placement
    ): string {
        $parts = [
            'div-gpt-ad',
            Str::slug($page),
            Str::slug($type),
        ];

        if ($placement !== null) {
            $parts[] = $placement;
        }

        $parts[] = Str::uuid();

        return implode('-', $parts);
    }
}
