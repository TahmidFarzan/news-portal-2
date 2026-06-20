<?php
namespace App\Services;

use App\Helpers\CacheServerHelper;
use App\Helpers\EventHelper;
use App\Helpers\PageHelper;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\News;
use App\Models\NewsPlacement;
use App\Models\NewsType;
use App\Models\Page;
use App\Models\Tag;
use App\Services\SiteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Str;

class PageService
{
    protected SiteService $siteService;

    public function __construct(SiteService $siteService)
    {
        $this->siteService = $siteService;
    }

    public function language(): Language
    {
        return $this->siteService->language();
    }

    public function page(string $slugTree): Page
    {
        $language = $this->language();

        $pageCacheKey = "page:language:{$language->locale}:page:{$slugTree}";

        $pageCacheTags = [
            'page',
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:page",
            "page:language:{$language->locale}:page:{$slugTree}",
        ];

        $pageCachedData = CacheServerHelper::getCachedData($pageCacheKey, $pageCacheTags);

        if (($pageCachedData !== null) && ($pageCachedData instanceof Page)) {
            return $pageCachedData;
        }

        $page = Page::query()
            ->with("language")
            ->where("language_id", $language->id)
            ->where("slug_tree", $slugTree)
            ->where("is_default", false)
            ->where("is_published", true)
            ->firstOrFail();

        CacheServerHelper::cachedData(
            $pageCacheKey,
            $page,
            CacheServerHelper::threeMinInSecond,
            $pageCacheTags
        );

        return $page;
    }

    public function homePage(): Page
    {
        $language = $this->language();

        $pageCacheKey = "page:language:{$language->locale}:page:home";

        $pageCacheTags = [
            'page',
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:page",
            "page:language:{$language->locale}:page:home",
        ];

        $pageCachedData = CacheServerHelper::getCachedData($pageCacheKey, $pageCacheTags);

        if (($pageCachedData !== null) && ($pageCachedData instanceof Page)) {
            return $pageCachedData;
        }

        $page = Page::query()
            ->with("language")
            ->where("language_id", $language->id)
            ->where("default_use_as", PageHelper::DAFAULT_USE_AS_HOME)
            ->where("is_default", true)
            ->where("is_published", true)
            ->firstOrFail();

        CacheServerHelper::cachedData(
            $pageCacheKey,
            $page,
            CacheServerHelper::threeMinInSecond,
            $pageCacheTags
        );

        return $page;
    }

    public function latestPage(): Page
    {
        $language = $this->language();

        $pageCacheKey = "page:language:{$language->locale}:page:latest";

        $pageCacheTags = [
            'page',
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:page",
            "page:language:{$language->locale}:page:latest",
        ];

        $pageCachedData = CacheServerHelper::getCachedData($pageCacheKey, $pageCacheTags);

        if (($pageCachedData !== null) && ($pageCachedData instanceof Page)) {
            return $pageCachedData;
        }

        $page = Page::query()
            ->with("language")
            ->where("language_id", $language->id)
            ->where("default_use_as", PageHelper::DAFAULT_USE_AS_LATEST)
            ->where("is_default", true)
            ->where("is_published", true)
            ->firstOrFail();

        CacheServerHelper::cachedData(
            $pageCacheKey,
            $page,
            CacheServerHelper::threeMinInSecond,
            $pageCacheTags
        );

        return $page;
    }

    public function searchPage(): Page
    {
        $language = $this->language();

        $pageCacheKey = "page:language:{$language->locale}:page:search";

        $pageCacheTags = [
            'page',
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:page",
            "page:language:{$language->locale}:page:search",
        ];

        $pageCachedData = CacheServerHelper::getCachedData($pageCacheKey, $pageCacheTags);

        if (($pageCachedData !== null) && ($pageCachedData instanceof Page)) {
            return $pageCachedData;
        }

        $page = Page::query()
            ->with("language")
            ->where("language_id", $language->id)
            ->where("default_use_as", PageHelper::DAFAULT_USE_AS_SEARCH)
            ->where("is_default", true)
            ->where("is_published", true)
            ->firstOrFail();

        CacheServerHelper::cachedData(
            $pageCacheKey,
            $page,
            CacheServerHelper::threeMinInSecond,
            $pageCacheTags
        );

        return $page;
    }

    public function news(string $slug): News
    {
        $language     = $this->language();
        $newsCacheKey = "page:news:{$slug}";

        $newsCacheTags = [
            'page',
            "page:news:{$slug}",
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
            ->where('language_id', $language->id)
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

        $language = $this->language();

        $tagCacheKey = "page:language:{$language->locale}:tag:{$slug}";

        $tagCacheTags = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:tag:{$slug}",
        ];

        $tagCachedData = CacheServerHelper::getCachedData($tagCacheKey, $tagCacheTags);

        if (($tagCachedData !== null) && ($tagCachedData instanceof Tag)) {
            return $tagCachedData;
        }

        $tag = Tag::query()
            ->where('language_id', $language->id)
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

        $language = $this->language();

        $tagNewsCacheKey = "page:language:{$language->locale}:tag:{$tag->slug}:news:per-page:{$perPage}:query:{$queryHash}";

        $tagNewsCacheTags = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:tag:{$tag->slug}",
            "page:language:{$language->locale}:tag:{$tag->slug}:news",
        ];

        $tagNewsCachedData = CacheServerHelper::getCachedData($tagNewsCacheKey, $tagNewsCacheTags);

        if (($tagNewsCachedData !== null) && ($tagNewsCachedData instanceof CursorPaginator)) {
            return $tagNewsCachedData;
        }

        $tagNews = News::query()
            ->with(["newsType", "category", "event", "location"])
            ->where("language_id", $language->id)
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
        $language            = $this->language();
        $contributorCacheKey = "page:language:{$language->locale}:contributor:{$slug}";

        $contributorCacheTags = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:contributor:{$slug}",
        ];

        $contributorCachedData = CacheServerHelper::getCachedData($contributorCacheKey, $contributorCacheTags);

        if (($contributorCachedData !== null) && ($contributorCachedData instanceof Contributor)) {
            return $contributorCachedData;
        }

        $contributor = Contributor::query()
            ->where("language_id", $language->id)
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

        $language = $this->language();

        $contributorNewsCacheKey = "page:language:{$language->locale}:contributor:{$contributor->slug}:news:per-page:{$perPage}:query:{$queryHash}";

        $contributorNewsCacheTags = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:contributor:{$contributor->slug}",
            "page:language:{$language->locale}:contributor:{$contributor->slug}:news",
        ];

        $contributorNewsCachedData = CacheServerHelper::getCachedData($contributorNewsCacheKey, $contributorNewsCacheTags);

        if (($contributorNewsCachedData !== null) && ($contributorNewsCachedData instanceof CursorPaginator)) {
            return $contributorNewsCachedData;
        }

        $contributorNews = News::query()
            ->with(["newsType", "category", "event", "location"])
            ->where("language_id", $language->id)
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
        $language      = $this->language();
        $eventCacheKey = "page:language:{$language->locale}:event:{$slug}";

        $eventCacheEvents = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:event:{$slug}",
        ];

        $eventCachedData = CacheServerHelper::getCachedData($eventCacheKey, $eventCacheEvents);

        if (($eventCachedData !== null) && ($eventCachedData instanceof Event)) {
            return $eventCachedData;
        }

        $event = Event::query()
            ->where('language_id', $language->id)
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
        $perPage   = $request->input('per_page', 12);
        $queryHash = md5(http_build_query($request->query()));

        $language = $this->language();

        $eventNewsCacheKey = "page:language:{$language->locale}:event:{$event->slug}:news:per-page:{$perPage}:query:{$queryHash}";

        $eventNewsCacheTags = [
            "page",
            "page:language:{$language->locale}:",
            "page:language:{$language->locale}:event:{$event->slug}",
            "page:language:{$language->locale}:event:{$event->slug}:news",
        ];

        $eventNewsCachedData = CacheServerHelper::getCachedData($eventNewsCacheKey, $eventNewsCacheTags);

        if (($eventNewsCachedData !== null) && ($eventNewsCachedData instanceof CursorPaginator)) {
            return $eventNewsCachedData;
        }

        $eventNews = News::query()
            ->with(["newsType", "category", "event", "location"])
            ->where("language_id", $language->id)
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
            $eventNewsCacheTags
        );

        return $eventNews;
    }

    public function category(string $slugTree): Category
    {
        $language = $this->language();

        $categoryCacheKey = "page:language:{$language->locale}:category:{$slugTree}";

        $categoryCacheCategorys = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:category:{$slugTree}",
        ];
        $categoryCachedData = CacheServerHelper::getCachedData($categoryCacheKey, $categoryCacheCategorys);

        if (($categoryCachedData !== null) && ($categoryCachedData instanceof Category)) {
            return $categoryCachedData;
        }

        $category = Category::query()->with(["parent", "children"])
            ->where("language_id", $language->id)
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
        $language = $this->language();

        $cacheKey = "page:language:{$language->locale}:category:{$category->slug}:locations:tree:max-depth-level";

        $cacheCategories = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:category:{$category->slug}",
            "page:language:{$language->locale}:category:{$category->slug}:locations",
            "page:language:{$language->locale}:category:{$category->slug}:locations:tree",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheCategories);

        if ($cachedData !== null && is_object($cachedData)) {
            return $cachedData;
        }

        $maxDepth = Location::withQueryConstraint(
            function (Builder $query) use ($category, $language) {
                $query->where('locations.category_id', $category->id)
                    ->where('locations.language_id', $language->id);
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

        $categoryId         = $category->id;
        $language           = $this->language();
        $pageSectionSlugKey = Str::lower(Str::slug($pageSection));

        $cacheKey = "page:language:{$language->locale}:category:{$category->slug}:page-section:{$pageSectionSlugKey}:news";

        $cacheCategories = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:category:{$category->slug}",
            "page:language:{$language->locale}:category:{$category->slug}:page-section:{$pageSectionSlugKey}",
            "page:language:{$language->locale}:category:{$category->slug}:page-section:{$pageSectionSlugKey}:news",
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
            ->where("language_id", $language->id)
            ->where('category_id', $categoryId)
            ->where('is_published', true)
            ->orderBy(
                NewsPlacement::query()
                    ->select('position')
                    ->where("language_id", $language->id)
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

        $language = $this->language();

        $categoryNewsCacheKey = "page:language:{$language->locale}:category:{$category->slug}:news:per-page:{$perPage}:query:{$queryHash}";

        $categoryNewsCacheTags = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:category:{$category->slug}",
            "page:language:{$language->locale}:category:{$category->slug}:news",
        ];

        $categoryNewsCachedData = CacheServerHelper::getCachedData($categoryNewsCacheKey, $categoryNewsCacheTags);

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
            ->where("language_id", $language->id)
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
            $categoryNewsCacheTags
        );

        return $categoryNews;
    }

    public function location(string $slugTree): Location
    {
        $language = $this->language();

        $locationCacheKey = "page:language:{$language->locale}:location:{$slugTree}";

        $locationCacheLocations = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:location:{$slugTree}",
        ];

        $locationCachedData = CacheServerHelper::getCachedData($locationCacheKey, $locationCacheLocations);

        if (($locationCachedData !== null) && ($locationCachedData instanceof Location)) {
            return $locationCachedData;
        }

        $location = Location::query()->with(["parent", "children"])
            ->where("language_id", $language->id)
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

        $language = $this->language();

        $locationNewsCacheKey = "page:language:{$language->locale}:location:{$location->slug}:news:per-page:{$perPage}:query:{$queryHash}";

        $locationNewsCacheTags = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:location:{$location->slug}",
            "page:language:{$language->locale}:location:{$location->slug}:news",
        ];

        $locationNewsCachedData = CacheServerHelper::getCachedData($locationNewsCacheKey, $locationNewsCacheTags);

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
            ->where("location_id", $language->id)
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
            $locationNewsCacheTags
        );

        return $locationNews;
    }

    public function newsSearch(Request $request)
    {
        $language = $this->language();

        $perPage = $request->input('per_page', 24);

        $queryHash = md5(http_build_query($request->query()));

        $newsCacheKey = "page:language:{$language->locale}:news-search:news:per-page:{$perPage}:query:{$queryHash}";

        $newsCacheTags = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:news-search",
            "page:language:{$language->locale}:news-search:news",
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
            $news = $news->whereIn('category_id', $categoryIds);
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
            $news = $news->whereIn('location_id', $locationIds);
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

        $newsCacheKey = "page:language:{$language->locale}:recent-news";

        $newsCacheTags = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:recent-news",
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
            ->limit(15)
            ->get();

        CacheServerHelper::cachedData(
            $newsCacheKey,
            $news,
            CacheServerHelper::threeMinInSecond,
            $newsCacheTags
        );

        return $news;
    }

    public function newsType(string $slug): NewsType
    {
        $language = $this->language();

        $newsTypeCacheKey = "page:language:{$language->locale}:news-type:{$slug}";

        $newsTypeCacheNewsType = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:news-type:{$slug}",
        ];
        $newsTypeCachedData = CacheServerHelper::getCachedData($newsTypeCacheKey, $newsTypeCacheNewsType);

        if (($newsTypeCachedData !== null) && ($newsTypeCachedData instanceof NewsType)) {
            return $newsTypeCachedData;
        }

        $newsType = NewsType::query()->where('slug', $slug)->firstOrFail();

        CacheServerHelper::cachedData(
            $newsTypeCacheKey,
            $newsType,
            CacheServerHelper::threeMinInSecond,
            $newsTypeCacheNewsType
        );

        return $newsType;
    }

    public function newsTypeNews(Request $request, NewsType $newsType)
    {
        $perPage   = $request->input('per_page', 24);
        $queryHash = md5(http_build_query($request->query()));

        $language = $this->language();

        $newsTypeNewsCacheKey = "page:language:{$language->locale}:news-type:{$newsType->slug}:news:per-page:{$perPage}:query:{$queryHash}";

        $newsTypeNewsCacheTags = [
            "page",
            "page:language:{$language->locale}",
            "page:language:{$language->locale}:news-type:{$newsType->slug}",
            "page:language:{$language->locale}:news-type:{$newsType->slug}:news",
        ];

        $newsTypeNewsCachedData = CacheServerHelper::getCachedData($newsTypeNewsCacheKey, $newsTypeNewsCacheTags);

        if (($newsTypeNewsCachedData !== null) && ($newsTypeNewsCachedData instanceof CursorPaginator)) {
            return $newsTypeNewsCachedData;
        }

        $newsTypeNews = News::query()
            ->with(["newsType", "category", "event", "location"])
            ->where("language_id", $language->id)
            ->where("is_published", true)
            ->where("news_type_id", $newsType->id)
            ->orderByDesc('id')
            ->orderByDesc('created_at')
            ->cursorPaginate($perPage)
            ->withQueryString();

        CacheServerHelper::cachedData(
            $newsTypeNewsCacheKey,
            $newsTypeNews,
            CacheServerHelper::threeMinInSecond,
            $newsTypeNewsCacheTags
        );

        return $newsTypeNews;
    }

    public function homeTopEvents()
    {
        $language = $this->language();

        $eventCacheKey = "page:home:language:{$language->locale}:position:top:event";

        $eventCacheTags = [
            "page",
            "page:home",
            "page:home:language:{$language->locale}",
            "page:home:language:{$language->locale}:position:top",
            "page:home:language:{$language->locale}:position:top:event",
        ];

        $eventCachedData = CacheServerHelper::getCachedData($eventCacheKey, $eventCacheTags);

        if (($eventCachedData !== null) && ($eventCachedData instanceof CursorPaginator)) {
            return $eventCachedData;
        }

        $events = Event::where("language_id", $language->id)->where("position", EventHelper::POSITION_TOP)->where("is_current", true)->get();

        CacheServerHelper::cachedData(
            $eventCacheKey,
            $events,
            CacheServerHelper::threeMinInSecond,
            $eventCacheTags
        );

        return $events;
    }

    public function homeBottomEvents()
    {
        $language = $this->language();

        $eventCacheKey = "page:home:language:{$language->locale}:position:bottom:event";

        $eventCacheTags = [
            "page",
            "page:home",
            "page:home:language:{$language->locale}",
            "page:home:language:{$language->locale}:position:bottom",
            "page:home:language:{$language->locale}:position:bottom:event",
        ];

        $eventCachedData = CacheServerHelper::getCachedData($eventCacheKey, $eventCacheTags);

        if (($eventCachedData !== null) && ($eventCachedData instanceof CursorPaginator)) {
            return $eventCachedData;
        }

        $events = Event::where("language_id", $language->id)->where("position", EventHelper::POSITION_BOTTOM)->where("is_current", true)->get();

        CacheServerHelper::cachedData(
            $eventCacheKey,
            $events,
            CacheServerHelper::threeMinInSecond,
            $eventCacheTags
        );

        return $events;
    }

    public function homeLeadNews()
    {
        $language    = $this->language();
        $page        = PageHelper::PAGE_HOME;
        $pageSection = PageHelper::PAGE_SECTION_LEAD_NEWS;

        $newsCacheKey = "page:home:language:{$language->locale}:lead-section:news";

        $newsCacheTags = [
            "page",
            "page:home",
            "page:home:language:{$language->locale}",
            "page:home:language:{$language->locale}:lead-section",
        ];

        $newsCachedData = CacheServerHelper::getCachedData($newsCacheKey, $newsCacheTags);

        if (($newsCachedData !== null) && ($newsCachedData instanceof CursorPaginator)) {
            return $newsCachedData;
        }

        $newsPlacementTable = (new NewsPlacement())->getTable();

        $news = News::query()
            ->with(["newsType", "category", "event", "location"])
            ->withWhereHas('newsPlacements', function ($query) use ($page, $pageSection) {
                $query->where('page', $page)
                    ->where('page_section', $pageSection);
            })
            ->where("language_id", $language->id)
            ->where('is_published', true)
            ->orderBy(
                NewsPlacement::query()
                    ->select('position')
                    ->where("language_id", $language->id)
                    ->whereColumn("{$newsPlacementTable}.news_id", 'news.id')
                    ->where('page', $page)
                    ->where('page_section', $pageSection)
                    ->limit(1),
                'asc'
            )
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        CacheServerHelper::cachedData(
            $newsCacheKey,
            $news,
            CacheServerHelper::threeMinInSecond,
            $newsCacheTags
        );

        return $news;
    }

    public function homeEventNews(Event $event)
    {
        $language = $this->language();

        $newsCacheKey = "page:home:language:{$language->locale}:event:{$event->slug}:news";

        $newsCacheTags = [
            "page",
            "page:home",
            "page:home:language:{$language->locale}",
            "page:home:language:{$language->locale}:event:{$event->slug}",
        ];

        $newsCachedData = CacheServerHelper::getCachedData($newsCacheKey, $newsCacheTags);

        if (($newsCachedData !== null) && ($newsCachedData instanceof CursorPaginator)) {
            return $newsCachedData;
        }

        $news = News::query()
            ->with(["newsType", "category", "event", "location"])
            ->where("event_id", $event->id)
            ->where("language_id", $language->id)
            ->where('is_published', true)
            ->orderBy('create_at', 'desc')
            ->orderBy("id", 'desc')
            ->limit(10)
            ->get();

        CacheServerHelper::cachedData(
            $newsCacheKey,
            $news,
            CacheServerHelper::threeMinInSecond,
            $newsCacheTags
        );

        return $news;
    }

    public function homeCategoryNews(Category $category)
    {
        $page        = PageHelper::PAGE_HOME;
        $pageSection = PageHelper::PAGE_SECTION_CATEGORY_NEWS;

        $categoryId         = $category->id;
        $language           = $this->language();
        $pageSectionSlugKey = Str::lower(Str::slug($pageSection));

        $newsCacheKey = "page:home:language:{$language->locale}:category:{$category->slug}:{$pageSectionSlugKey}:news";

        $newsCacheTags = [
            "page",
            "page:home",
            "page:home:language:{$language->locale}",
            "page:home:language:{$language->locale}:category:{$category->slug}",
            "page:home:language:{$language->locale}:category:{$category->slug}:{$pageSectionSlugKey}",
        ];

        $newsCachedData = CacheServerHelper::getCachedData($newsCacheKey, $newsCacheTags);

        if (($newsCachedData !== null) && ($newsCachedData instanceof CursorPaginator)) {
            return $newsCachedData;
        }

        $newsPlacementTable = (new NewsPlacement())->getTable();

        $news = News::query()
            ->with(["newsType", "category", "event", "location"])
            ->withWhereHas('newsPlacements', function ($query) use ($categoryId, $page, $pageSection) {
                $query->where('category_id', $categoryId)
                    ->where('page', $page)
                    ->where('page_section', $pageSection);
            })
            ->where("language_id", $language->id)
            ->where('category_id', $categoryId)
            ->where('is_published', true)
            ->orderBy(
                NewsPlacement::query()
                    ->select('position')
                    ->where("language_id", $language->id)
                    ->whereColumn("{$newsPlacementTable}.news_id", 'news.id')
                    ->where('category_id', $categoryId)
                    ->where('page', $page)
                    ->where('page_section', $pageSection)
                    ->limit(1),
                'asc'
            )
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        CacheServerHelper::cachedData(
            $newsCacheKey,
            $news,
            CacheServerHelper::threeMinInSecond,
            $newsCacheTags
        );

        return $news;
    }

    public function homeNewsTypeNews(NewsType $newsType)
    {
        $language     = $this->language();
        $newsCacheKey = "page:home:language:{$language->locale}:news-type:{$newsType->slug}:news";

        $newsCacheTags = [
            "page",
            "page:home",
            "page:home:language:{$language->locale}",
            "page:home:language:{$language->locale}:news-type:{$newsType->slug}",
        ];

        $newsCachedData = CacheServerHelper::getCachedData($newsCacheKey, $newsCacheTags);

        if (($newsCachedData !== null) && ($newsCachedData instanceof CursorPaginator)) {
            return $newsCachedData;
        }

        $news = News::query()
            ->with(["newsType", "category", "event", "location"])
            ->where("language_id", $language->id)
            ->where('news_type_id', $newsType->id)
            ->where('is_published', true)
            ->orderBy('created_at', "desc")
            ->orderByDesc('id')
            ->limit(10)
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
