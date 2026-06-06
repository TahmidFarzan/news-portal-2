<?php
namespace Database\Seeders;

use App\Models\BreakingNews;
use App\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BreakingNewsSeeder extends Seeder
{
    public function run(): void
    {
        if (config('database.default') === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF;');
            BreakingNews::query()->delete();
            DB::statement('PRAGMA foreign_keys = ON;');
        }

        if (config('database.default') === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            BreakingNews::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        if (in_array(config('database.default'), ['pgsql', 'sqlsrv'])) {
            BreakingNews::truncate();
        }

        $news = News::query()
            ->latest()
            ->limit(25)
            ->get();

        foreach ($news as $perNews) {
            BreakingNews::factory()->state([
                'title'        => $perNews?->title,
                "language_id"  => $perNews->language_id,

                "news_id"      => $perNews?->id ?? null,
                'created_at'   => $perNews->created_at,
                'updated_at'   => $perNews->updated_at,
                "is_published" => true,
            ])->create();
        }

    }
}
