<?php
namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RelatedNewsSyncSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('news_related_news')->truncate();

        $latestNews = News::query()
            ->latest()
            ->limit(15)
            ->get();

        $latestNewsIds = $latestNews->pluck('id')->all();

        $randomNews = News::query()
            ->whereNotIn('id', $latestNewsIds)
            ->inRandomOrder()
            ->limit(155)
            ->get();

        $newsItems = $latestNews
            ->merge($randomNews)
            ->unique('id')
            ->values();

        foreach ($newsItems as $news) {
            $relatedNewsIds = News::query()
                ->where('language_id', $news->language_id)
                ->where('id', '!=', $news->id)
                ->inRandomOrder()
                ->limit(rand(3, 5))
                ->pluck('id')
                ->all();

            $news->relatedNews()->sync($relatedNewsIds);
        }
    }
}
