<?php
namespace App\Services;

use App\Helpers\CacheServerHelper;
use App\Helpers\PageHelper;
use App\Helpers\SystemHelper;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\News;
use App\Models\NewsPlacement;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Str;

class PageService
{

    public function language(): Language
    {
        $languageCode = SystemHelper::LANGUAGE_DEFAULT_CODE;

        $language = Language::query()->where('code', $languageCode)->firstOrFail();

        return $language;
    }

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
            ->with(["newsType", "category", "event", "location"])
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
            ->with(["newsType", "category", "event", "location"])
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
            ->with(["newsType", "category", "event", "location"])
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

    public function category(string $slugTree): Category
    {
        $categoryCacheKey = "category-details:{$slugTree}";

        $categoryCacheCategorys = [
            'categorys',
            "category-details:{$slugTree}",
        ];

        $categoryCachedData = CacheServerHelper::getCachedData($categoryCacheKey, $categoryCacheCategorys);

        if (($categoryCachedData !== null) && ($categoryCachedData instanceof Category)) {
            return $categoryCachedData;
        }

        $category = Category::query()->with(["parent", "children"])
            ->where('slug_tree', $slugTree)
            ->firstOrFail();

        CacheServerHelper::cachedData(
            $categoryCacheKey,
            $category,
            CacheServerHelper::threeMinInSecond,
            $categoryCacheCategorys
        );

        return $category;
    }

    public function categoryLocationMaxDepthAndLevel(Category $category): object
    {
        $cacheKey = "category:{$category->slug}:locations:tree:max-depth-level";

        $cacheCategories = [
            'category',
            "category:{$category->slug}:locations",
            "category:{$category->slug}:locations:tree",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheCategories);

        if ($cachedData !== null && is_object($cachedData)) {
            return $cachedData;
        }

        $maxDepth = Location::withQueryConstraint(
            function (Builder $query) use ($category) {
                $query->where('locations.category_id', $category->id);
            },
            function () use ($category) {
                return Location::treeOf(function (Builder $query) use ($category) {
                    $query->whereNull('locations.parent_id')
                        ->where('locations.category_id', $category->id);
                })
                    ->max('depth');
            }
        );

        $maxDepth = $maxDepth !== null ? (int) $maxDepth : null;

        $data = (object) [
            'max_depth' => $maxDepth ?? 0,
            'max_level' => $maxDepth !== null ? $maxDepth + 1 : 0,
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::threeMinInSecond,
            $cacheCategories
        );

        return $data;
    }

    public function categoryNewsPlacement(Category $category)
    {
        $page        = PageHelper::PAGE_CATEGORY;
        $pageSection = PageHelper::PAGE_SECTION_LEAD_NEWS;

        $categoryId = $category->id;

        $pageSlugKey        = Str::lower(Str::slug($page));
        $pageSectionSlugKey = Str::lower(Str::slug($pageSection));

        $cacheKey = "page:{$pageSlugKey}:page-section:{$pageSectionSlugKey}:category:{$category->slug}:news";

        $cacheCategories = [
            'page',
            "page:{$pageSlugKey}:page-section:{$pageSectionSlugKey}",
            "page:{$pageSlugKey}:page-section:{$pageSectionSlugKey}:category:{$category->slug}",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheCategories);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $newsPlacementTable = (new NewsPlacement())->getTable();

        $news = News::query()
            ->with(["newsType", "category", "event", "location"])
            ->withWhereHas('newsPlacements', function ($query) use ($categoryId, $page, $pageSection) {
                $query->where('category_id', $categoryId)
                    ->where('page', $page)
                    ->where('page_section', $pageSection);
            })
            ->where('category_id', $categoryId)
            ->where('is_published', true)
            ->orderBy(
                NewsPlacement::query()
                    ->select('position')
                    ->whereColumn("{$newsPlacementTable}.news_id", 'news.id')
                    ->where('category_id', $categoryId)
                    ->where('page', $page)
                    ->where('page_section', $pageSection)
                    ->limit(1),
                'asc'
            )
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        CacheServerHelper::cachedData(
            $cacheKey,
            $news,
            CacheServerHelper::threeMinInSecond,
            $cacheCategories
        );

        return $news;
    }

    public function categoryNews(Request $request, Category $category)
    {
        $perPage   = $request->input('per_page', 24);
        $queryHash = md5(http_build_query($request->query()));

        $categoryNewsCacheKey = "category:{$category->slug}:news:per-page:{$perPage}:query:{$queryHash}";

        $categoryNewsCacheCategorys = [
            'category',
            'category-news',
            "category:{$category->slug}:news",
        ];

        $categoryNewsCachedData = CacheServerHelper::getCachedData($categoryNewsCacheKey, $categoryNewsCacheCategorys);

        if (($categoryNewsCachedData !== null) && ($categoryNewsCachedData instanceof CursorPaginator)) {
            return $categoryNewsCachedData;
        }

        $categoryIds = [];

        array_push($categoryIds, $category->id);

        foreach ($category->children as $perChildren) {
            array_push($categoryIds, $perChildren->id);
        }

        $categoryNews = News::query()
            ->with(["newsType", "category", "event", "location"])
            ->where("is_published", true)
            ->whereIn("category_id", $categoryIds)
            ->orderByDesc('id')
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage)
            ->withQueryString();

        CacheServerHelper::cachedData(
            $categoryNewsCacheKey,
            $categoryNews,
            CacheServerHelper::threeMinInSecond,
            $categoryNewsCacheCategorys
        );

        return $categoryNews;
    }

    public function location(string $slugTree): Location
    {
        $locationCacheKey = "location-details:{$slugTree}";

        $locationCacheLocations = [
            'locations',
            "location-details:{$slugTree}",
        ];

        $locationCachedData = CacheServerHelper::getCachedData($locationCacheKey, $locationCacheLocations);

        if (($locationCachedData !== null) && ($locationCachedData instanceof Location)) {
            return $locationCachedData;
        }

        $location = Location::query()->with(["parent", "children"])
            ->where('slug_tree', $slugTree)
            ->firstOrFail();

        CacheServerHelper::cachedData(
            $locationCacheKey,
            $location,
            CacheServerHelper::threeMinInSecond,
            $locationCacheLocations
        );

        return $location;
    }

    public function locationNews(Request $request, Location $location)
    {
        $perPage   = $request->input('per_page', 24);
        $queryHash = md5(http_build_query($request->query()));

        $locationNewsCacheKey = "location:{$location->slug}:news:per-page:{$perPage}:query:{$queryHash}";

        $locationNewsCacheLocations = [
            'location',
            'location-news',
            "location:{$location->slug}:news",
        ];

        $locationNewsCachedData = CacheServerHelper::getCachedData($locationNewsCacheKey, $locationNewsCacheLocations);

        if (($locationNewsCachedData !== null) && ($locationNewsCachedData instanceof CursorPaginator)) {
            return $locationNewsCachedData;
        }

        $locationIds = [];

        array_push($locationIds, $location->id);

        foreach ($location->children as $perChildren) {
            array_push($locationIds, $perChildren->id);
        }

        $locationNews = News::query()
            ->with(["newsType", "category", "event", "location"])
            ->where("is_published", true)
            ->whereIn("location_id", $locationIds)
            ->orderByDesc('id')
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage)
            ->withQueryString();

        CacheServerHelper::cachedData(
            $locationNewsCacheKey,
            $locationNews,
            CacheServerHelper::threeMinInSecond,
            $locationNewsCacheLocations
        );

        return $locationNews;
    }

    public function newsSearch(Request $request)
    {
        $language = $this->language();

        $perPage = $request->input('per_page', 24);

        $queryHash = md5(http_build_query($request->query()));

        $newsCacheKey = "news:language:{$language->slug}:per-page:{$perPage}:query:{$queryHash}";

        $newsCacheTags = [
            'news',
            "news:language:{$language->slug}",
        ];

        $newsCachedData = CacheServerHelper::getCachedData($newsCacheKey, $newsCacheTags);

        if (($newsCachedData !== null) && ($newsCachedData instanceof CursorPaginator)) {
            return $newsCachedData;
        }

        $news = News::query()->with(["newsType", "category", "event", "location"])
            ->where('language_id', $language->id)
            ->where("is_published", true);

        if ($request->filled('news_type_id')) {
            $news = $news->where('news_type_id', $request->input('news_type_id'));
        }

        if ($request->filled('category_id')) {
            $categoryIds = [];

            $category = $this->categoryById($request->input('category_id'));
            array_push($categoryIds, $request->input('category_id'));

            if ($category) {
                foreach ($category->children as $perChildren) {
                    array_push($categoryIds, $perChildren->id);
                }
            }
            $news = $news->whereIn('category_id',  $categoryIds);
        }

        if ($request->filled('event_id')) {
            $news = $news->where('event_id', $request->input('event_id'));
        }

        if ($request->filled('location_id')) {
            $locationIds = [];

            $location = $this->locationById($request->input('location_id'));
            array_push($locationIds, $request->input('location_id'));

            if ($location) {
                foreach ($location->children as $perChildren) {
                    array_push($locationIds, $perChildren->id);
                }
            }
            $news = $news->whereIn('location_id',  $locationIds);
        }

        if ($request->filled("tag_id")) {
            $tagId = $request->input('tag_id');
            $news  = $news->whereHas('tags', function ($tagQuery) use ($tagId) {
                $tagQuery->where('id', $tagId);
            });
        }

        if ($request->filled("contributor_id")) {
            $contributorId = $request->input('contributor_id');
            $news          = $news->whereHas('contributors', function ($contributorQuery) use ($contributorId) {
                $contributorQuery->where('id', $contributorId);
            });
        }

        if ($request->filled('date')) {
            $date = $request->input('date');
            $date = is_string($date) ? new \DateTime($date) : $date;
            $news = $news->whereDate('created_at', '<=', $date);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');

            $news = $news->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('sub_title', 'like', "%{$search}%")
                    ->orWhere('content_shoulder', 'like', "%{$search}%")
                    ->orWhere('brief', 'like', "%{$search}%")
                    ->orWhere('seo_brief', 'like', '%' . $search . '%')
                    ->orWhere('seo_title', 'like', '%' . $search . '%')
                    ->orWhere('source', 'like', "%{$search}%");
            });
        }

        $news = $news
            ->orderByDesc('id')
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage)
            ->withQueryString();

        CacheServerHelper::cachedData(
            $newsCacheKey,
            $news,
            CacheServerHelper::threeMinInSecond,
            $newsCacheTags
        );

        return $news;
    }


    public function recentNews()
    {
        $language = $this->language();

        $newsCacheKey = "news:recent-news:language:{$language->slug}";

        $newsCacheTags = [
            'news',
            'news:recent-news',
            "news:recent-news:language:{$language->slug}",
        ];

        $newsCachedData = CacheServerHelper::getCachedData($newsCacheKey, $newsCacheTags);

        if (($newsCachedData !== null) && ($newsCachedData instanceof CursorPaginator)) {
            return $newsCachedData;
        }

        $news = News::query()->with(["newsType", "category", "event", "location"])
            ->where('language_id', $language->id)
            ->where("is_published", true)
            ->orderByDesc('id')
            ->orderByDesc('created_at')
            ->limit(25)
            ->get();

        CacheServerHelper::cachedData(
            $newsCacheKey,
            $news,
            CacheServerHelper::threeMinInSecond,
            $newsCacheTags
        );

        return $news;
    }

    private function categoryById(string | int $slugOrId): Category
    {
        return Category::with("children")->where("id", $slugOrId)->orWhere("slug", $slugOrId)->first();
    }

    private function locationById(string | int $slugOrId): Location
    {
        return Location::with("children")->where("id", $slugOrId)->orWhere("slug", $slugOrId)->first();
    }
}
