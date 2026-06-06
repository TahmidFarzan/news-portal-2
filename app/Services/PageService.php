<?php
namespace App\Services;

use App\Helpers\CacheServerHelper;
//use App\Models\Language;
use App\Helpers\SystemHelper;
use App\Models\News;

class PageService
{
    public function news(string $slug): News
    {
        $languageCode = SystemHelper::LANGUAGE_DEFAULT_CODE;

        $newsCacheKey = "news {$languageCode} details {$slug}";

        $newsCacheTags = [
            'news',
            'news-details',
            "news-details-{$slug}",
        ];

        $newsCachedData = CacheServerHelper::getCachedData($newsCacheKey, $newsCacheTags);

        if (($newsCachedData !== null) && ($newsCachedData instanceof News)) {
            return $newsCachedData;
        }

        $news = News::query()
            ->with([
                'newsType',
                'category',

                'event',
                'location',

                'tags',
                'tags.trend',

                'contributors',

                'relevantNews' => fn($query) => $query
                    ->orderByDesc('news.created_at')
                    ->limit(4),

                'relevantNews.category',

                'relatedNews'  => fn($query)  => $query
                    ->orderByDesc('news.created_at')
                    ->limit(4),
                'relatedNews.category',
            ])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->whereRelation('language', 'code', $languageCode)
            ->firstOrFail();

        CacheServerHelper::cachedData(
            $newsCacheKey,
            $news,
            CacheServerHelper::threeMinInSecond,
            $newsCacheTags
        );

        return $news;
    }

}
