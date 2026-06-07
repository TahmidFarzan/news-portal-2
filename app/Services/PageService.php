<?php
namespace App\Services;

use App\Helpers\CacheServerHelper;
//use App\Models\Language;
use App\Helpers\SystemHelper;
use App\Models\News;
use App\Models\Tag;
use Illuminate\Http\Request;

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

    public function tag(string $slug): Tag
    {
        return Tag::where("slug", $slug)->firstOrFail();
    }

    public function tagNews(Request $request, Tag $tag)
    {
        $perPage = $request->input('per_page', 24);

        $query = News::query()->with(["newsType", "category"])
            ->where("is_published", true);

        $query->whereHas('tags', function ($tagQuery) use ($tag) {
            $tagQuery->where('id', $tag->id);
        });

        $query = $query
            ->orderByDesc('id')
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage)
            ->withQueryString();
        return $query;
    }

    public function newsSearch(Request $request)
    {
        $perPage = $request->input('per_page', 15);

        $query = News::query()->with(["newsType", "category"])
            ->where("is_published", true);

        if ($request->filled("tag_id")) {
            $tagId = $request->input('tag_id');
            $query->whereHas('tags', function ($tagQuery) use ($tagId) {
                $tagQuery->where('id', $tagId);
            });
        }

        $query = $query
            ->orderByDesc('id')
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage)
            ->withQueryString();
        return $query;
    }
}
