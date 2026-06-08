<?php
namespace App\Services;

use App\Helpers\CacheServerHelper;
//use App\Models\Language;
use App\Models\Contributor;
use App\Models\News;
use App\Models\Tag;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;

class PageService
{
    public function news(string $slug): News
    {
        $newsCacheKey = "news-details:{$slug}";

        $newsCacheTags = [
            'news',
            "news-details:{$slug}",
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

        $tagCacheKey = "tag-details:{$slug}";

        $tagCacheTags = [
            'tags',
            "tag-details:{$slug}",
        ];

        $tagCachedData = CacheServerHelper::getCachedData($tagCacheKey, $tagCacheTags);

        if (($tagCachedData !== null) && ($tagCachedData instanceof Tag)) {
            return $tagCachedData;
        }

        $tag = Tag::query()
            ->where('slug', $slug)
            ->firstOrFail();

        CacheServerHelper::cachedData(
            $tagCacheKey,
            $tag,
            CacheServerHelper::threeMinInSecond,
            $tagCacheTags
        );

        return $tag;
    }

    public function tagNews(Request $request, Tag $tag)
    {
        $perPage   = $request->input('per_page', 24);
        $queryHash = md5(http_build_query($request->query()));

        $tagNewsCacheKey = "tag:{$tag->slug}:news:per-page:{$perPage}:query:{$queryHash}";

        $tagNewsCacheTags = [
            'tag',
            'tag-news',
            "tag:{$tag->slug}:news",
        ];

        $tagNewsCachedData = CacheServerHelper::getCachedData($tagNewsCacheKey, $tagNewsCacheTags);

        if (($tagNewsCachedData !== null) && ($tagNewsCachedData instanceof CursorPaginator)) {
            return $tagNewsCachedData;
        }

        $tagNews = News::query()
            ->with(["newsType", "category"])
            ->where("is_published", true)
            ->whereHas('tags', function ($tagQuery) use ($tag) {
                $tagQuery->where('tags.id', $tag->id);
            })
            ->orderByDesc('id')
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage)
            ->withQueryString();

        CacheServerHelper::cachedData(
            $tagNewsCacheKey,
            $tagNews,
            CacheServerHelper::threeMinInSecond,
            $tagNewsCacheTags
        );

        return $tagNews;
    }

    public function contributor(string $slug): Contributor
    {

        $contributorCacheKey = "contributor-details:{$slug}";

        $contributorCacheTags = [
            'contributors',
            "contributor-details:{$slug}",
        ];

        $contributorCachedData = CacheServerHelper::getCachedData($contributorCacheKey, $contributorCacheTags);

        if (($contributorCachedData !== null) && ($contributorCachedData instanceof Contributor)) {
            return $contributorCachedData;
        }

        $contributor = Contributor::query()
            ->where('slug', $slug)
            ->firstOrFail();

        CacheServerHelper::cachedData(
            $contributorCacheKey,
            $contributor,
            CacheServerHelper::threeMinInSecond,
            $contributorCacheTags
        );

        return $contributor;
    }

    public function contributorNews(Request $request, Contributor $contributor)
    {
        $perPage   = $request->input('per_page', 24);
        $queryHash = md5(http_build_query($request->query()));

        $contributorNewsCacheKey = "contributor:{$contributor->slug}:news:per-page:{$perPage}:query:{$queryHash}";

        $contributorNewsCacheTags = [
            'contributor',
            'contributor-news',
            "contributor:{$contributor->slug}:news",
        ];

        $contributorNewsCachedData = CacheServerHelper::getCachedData($contributorNewsCacheKey, $contributorNewsCacheTags);

        if (($contributorNewsCachedData !== null) && ($contributorNewsCachedData instanceof CursorPaginator)) {
            return $contributorNewsCachedData;
        }

        $contributorNews = News::query()
            ->with(["newsType", "category"])
            ->where("is_published", true)
            ->whereHas('contributors', function ($contributorQuery) use ($contributor) {
                $contributorQuery->where('contributors.id', $contributor->id);
            })
            ->orderByDesc('id')
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage)
            ->withQueryString();

        CacheServerHelper::cachedData(
            $contributorNewsCacheKey,
            $contributorNews,
            CacheServerHelper::threeMinInSecond,
            $contributorNewsCacheTags
        );

        return $contributorNews;
    }

    public function event(string $slug): Event
    {

        $eventCacheKey = "event-details:{$slug}";

        $eventCacheEvents = [
            'events',
            "event-details:{$slug}",
        ];

        $eventCachedData = CacheServerHelper::getCachedData($eventCacheKey, $eventCacheEvents);

        if (($eventCachedData !== null) && ($eventCachedData instanceof Event)) {
            return $eventCachedData;
        }

        $event = Event::query()
            ->where('slug', $slug)
            ->firstOrFail();

        CacheServerHelper::cachedData(
            $eventCacheKey,
            $event,
            CacheServerHelper::threeMinInSecond,
            $eventCacheEvents
        );

        return $event;
    }

    public function eventNews(Request $request, Event $event)
    {
        $perPage   = $request->input('per_page', 24);
        $queryHash = md5(http_build_query($request->query()));

        $eventNewsCacheKey = "event:{$event->slug}:news:per-page:{$perPage}:query:{$queryHash}";

        $eventNewsCacheEvents = [
            'event',
            'event-news',
            "event:{$event->slug}:news",
        ];

        $eventNewsCachedData = CacheServerHelper::getCachedData($eventNewsCacheKey, $eventNewsCacheEvents);

        if (($eventNewsCachedData !== null) && ($eventNewsCachedData instanceof CursorPaginator)) {
            return $eventNewsCachedData;
        }

        $eventNews = News::query()
            ->with(["newsType", "category"])
            ->where("is_published", true)
            ->whereNotNull("event_id")
            ->where("event_id", $event->id)
            ->orderByDesc('id')
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage)
            ->withQueryString();

        CacheServerHelper::cachedData(
            $eventNewsCacheKey,
            $eventNews,
            CacheServerHelper::threeMinInSecond,
            $eventNewsCacheEvents
        );

        return $eventNews;
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
