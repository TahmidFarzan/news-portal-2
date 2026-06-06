<?php
namespace Database\Seeders;

use App\Models\News;
use App\Models\Contributor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsContributorSyncSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('contributor_news')->truncate();

        $contributorsByLanguage = Contributor::query()
            ->get(['id', 'language_id'])
            ->groupBy('language_id');

        if ($contributorsByLanguage->isEmpty()) {
            return;
        }

        News::query()
            ->select(['id', 'language_id'])
            ->chunkById(1000, function ($news) use ($contributorsByLanguage) {
                $now  = now();
                $rows = [];

                foreach ($news as $perNews) {
                    $contributorIds = $contributorsByLanguage
                        ->get($perNews->language_id, collect())
                        ->pluck('id');

                    if ($contributorIds->isEmpty()) {
                        continue;
                    }

                    $randomContributorIds = $contributorIds->random(
                        min(rand(3, 5), $contributorIds->count())
                    );

                    foreach ($randomContributorIds as $contributorId) {
                        $rows[] = [
                            'news_id'    => $perNews->id,
                            'contributor_id'     => $contributorId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (! empty($rows)) {
                    DB::table('contributor_news')->insert($rows);
                }
            });
    }
}
