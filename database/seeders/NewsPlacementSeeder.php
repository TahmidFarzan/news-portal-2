<?php
namespace Database\Seeders;

use App\Helpers\PageHelper;
use App\Models\News;
use App\Models\NewsPlacement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsPlacementSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            NewsPlacement::query()->delete();
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            NewsPlacement::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            NewsPlacement::truncate();
        }

        $news = News::query()
            ->latest()
            ->limit(155)
            ->get();

        foreach ($news as $perNews) {
            $lastHomeLastLeadNewsPosition     = NewsPlacement::query()->where('page', PageHelper::PAGE_HOME)->where('page_section', PageHelper::PAGE_SECTION_LEAD_NEWS)->max('position');
            $lastHomeLastCategoryNewsPosition = NewsPlacement::query()->where('page', PageHelper::PAGE_HOME)->where('page_section', PageHelper::PAGE_SECTION_CATEGORY_NEWS)->where("category_id", $perNews->category_id)->max('position');
            $lastCategoryLastLeadNewsPosition = NewsPlacement::query()->where('page', PageHelper::PAGE_CATEGORY)->where('page_section', PageHelper::PAGE_SECTION_LEAD_NEWS)->where("category_id", $perNews->category_id)->max('position');

            $newsPlacements = [
                [
                    'news_id'      => $perNews->id,
                    'page'         => PageHelper::PAGE_HOME,
                    'page_section' => PageHelper::PAGE_SECTION_LEAD_NEWS,
                    'category_id'  => null,
                    'position'     => $lastHomeLastLeadNewsPosition + 1,
                ],
                [
                    'news_id'      => $perNews->id,
                    'page'         => PageHelper::PAGE_HOME,
                    'page_section' => PageHelper::PAGE_SECTION_CATEGORY_NEWS,
                    'category_id'  => $perNews->category_id,
                    'position'     => $lastHomeLastCategoryNewsPosition + 1,
                ],
                [
                    'news_id'      => $perNews->id,
                    'page'         => PageHelper::PAGE_CATEGORY,
                    'page_section' => PageHelper::PAGE_SECTION_LEAD_NEWS,
                    'category_id'  => $perNews->category_id,
                    'position'     => $lastCategoryLastLeadNewsPosition + 1,
                ],
            ];

            foreach ($newsPlacements as $newsPlacement) {
                NewsPlacement::factory()->state([
                    ...$newsPlacement,
                ])->create();
            }
        }
    }
}
