<?php
namespace Database\Seeders;

use App\Models\News;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NewsTagSyncSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('news_tag')->truncate();

        $tagsByLanguage = Tag::query()
            ->get(['id', 'language_id'])
            ->groupBy('language_id');

        if ($tagsByLanguage->isEmpty()) {
            return;
        }

        News::query()
            ->select(['id', 'language_id'])
            ->chunkById(1000, function ($news) use ($tagsByLanguage) {
                $now  = now();
                $rows = [];

                foreach ($news as $perNews) {
                    $tagIds = $tagsByLanguage
                        ->get($perNews->language_id, collect())
                        ->pluck('id');

                    if ($tagIds->isEmpty()) {
                        continue;
                    }

                    $randomTagIds = $tagIds->random(
                        min(rand(3, 5), $tagIds->count())
                    );

                    foreach ($randomTagIds as $tagId) {
                        $rows[] = [
                            'news_id'    => $perNews->id,
                            'tag_id'     => $tagId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if (! empty($rows)) {
                    DB::table('news_tag')->insert($rows);
                }
            });
    }
}
