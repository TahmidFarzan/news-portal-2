<?php

namespace App\Services;

use App\Helpers\CacheHelper;
use App\Helpers\EventHelper;
use App\Helpers\PageHelper;
use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\News;
use App\Models\NewsType;
use App\Models\Page;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionResult;
use App\Models\Tag;
use App\Services\Cache\CategoryCacheService;
use App\Services\Cache\ContributorCacheService;
use App\Services\Cache\EventCacheService;
use App\Services\Cache\LocationCacheService;
use App\Services\Cache\NewsCacheService;
use App\Services\Cache\NewsTypeCacheService;
use App\Services\Cache\PageCacheService;
use App\Services\Cache\SurveyCacheService;
use App\Services\Cache\TagCacheService;
use Exception;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PageService
{
    protected int $cachedTTL = 300;

    protected SiteService $siteService;

    protected NewsCacheService $newsCacheService;

    protected NewsTypeCacheService $newsTypeCacheService;

    protected CategoryCacheService $categoryCacheService;

    protected TagCacheService $tagCacheService;

    protected EventCacheService $eventCacheService;

    protected ContributorCacheService $contributorCacheService;

    protected PageCacheService $pageCacheService;

    protected LocationCacheService $locationCacheService;

    protected SurveyCacheService $surveyCacheService;

    public function __construct(
        SiteService $siteService,
        NewsCacheService $newsCacheService,
        CategoryCacheService $categoryCacheService,
        TagCacheService $tagCacheService,
        EventCacheService $eventCacheService,
        ContributorCacheService $contributorCacheService,
        PageCacheService $pageCacheService,
        NewsTypeCacheService $newsTypeCacheService,
        LocationCacheService $locationCacheService,
        SurveyCacheService $surveyCacheService
    ) {
        $this->siteService = $siteService;
        $this->newsCacheService = $newsCacheService;
        $this->categoryCacheService = $categoryCacheService;
        $this->tagCacheService = $tagCacheService;
        $this->eventCacheService = $eventCacheService;
        $this->contributorCacheService = $contributorCacheService;
        $this->pageCacheService = $pageCacheService;
        $this->newsTypeCacheService = $newsTypeCacheService;
        $this->locationCacheService = $locationCacheService;
        $this->surveyCacheService = $surveyCacheService;
    }

    public function language(): Language
    {
        return $this->siteService->language();
    }

    public function page(string $slugTree): Page
    {
        return $this->pageCacheService->getRecordBySlugTree(
            CacheHelper::KEY_PAGE,
            $slugTree,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function homePage(): Page
    {
        return $this->pageCacheService->getRecordByUseAs(
            CacheHelper::KEY_PAGE,
            PageHelper::DAFAULT_USE_AS_HOME,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function latestPage(): Page
    {
        return $this->pageCacheService->getRecordByUseAs(
            CacheHelper::KEY_PAGE,
            PageHelper::DAFAULT_USE_AS_LATEST,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function searchPage(): Page
    {
        return $this->pageCacheService->getRecordByUseAs(
            CacheHelper::KEY_PAGE,
            PageHelper::DAFAULT_USE_AS_SEARCH,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function newsType(string $slug): NewsType
    {
        return $this->newsTypeCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $slug,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function category(string $slugTree): Category
    {
        return $this->categoryCacheService->getRecordBySlugTree(
            CacheHelper::KEY_PAGE,
            $slugTree,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function categoryById(string $id): Category
    {
        return $this->categoryCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $id,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function categoryBySlug(string $slug): Category
    {
        return $this->categoryCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $slug,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function event(string $slug): Event
    {
        return $this->eventCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $slug,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function tag(string $slug): Tag
    {
        return $this->tagCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $slug,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function contributor(string $slug): Contributor
    {
        return $this->contributorCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $slug,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function location(string $slugTree): Location
    {
        return $this->locationCacheService->getRecordBySlugTree(
            CacheHelper::KEY_PAGE,
            $slugTree,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function locationById(string $id): Location
    {
        return $this->locationCacheService->getRecordById(
            CacheHelper::KEY_PAGE,
            $id,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function news(string $slug): News
    {
        return $this->newsCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $slug,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function categoryLocationMaxDepthAndLevel(Category $category): object
    {
        return $this->locationCacheService->getMaxDepthAndLevel(
            CacheHelper::KEY_PAGE,
            $category,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function categoryNewsPlacement(Category $category): Collection
    {
        return $this->newsCacheService->getRecordsLimitAccrodingNewsPlacement(
            CacheHelper::KEY_PAGE,
            PageHelper::PAGE_CATEGORY,
            PageHelper::PAGE_SECTION_LEAD_NEWS,
            $category,
            $this->language(),
            5,
            $this->cachedTTL
        );
    }

    public function recentNews(): CursorPaginator
    {
        return $this->newsCacheService->getLatestRecord(
            CacheHelper::KEY_PAGE,
            $this->language(),
            15,
            true,
            $this->cachedTTL
        );
    }

    public function popularNews(): Collection
    {
        return $this->newsCacheService->getPopulerRecord(
            CacheHelper::KEY_PAGE,
            $this->language(),
            15,
            $this->cachedTTL
        );
    }

    public function newsTypeNews(Request $request, NewsType $newsType): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $request,
            $newsType,
            $this->language(),
            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function categoryNews(Request $request, Category $category): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $request,
            $category,
            $this->language(),
            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function eventNews(Request $request, Event $event): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $request,
            $event,
            $this->language(),
            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function locationNews(Request $request, Location $location): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $request,
            $location,
            $this->language(),
            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function tagNews(Request $request, Tag $tag): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $request,
            $tag,
            $this->language(),
            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function contributorNews(Request $request, Contributor $contributor): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $request,
            $contributor,
            $this->language(),
            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function newsSearch(Request $request): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $request,
            null,
            $this->language(),
            $request->input('per_page', 16),
            $this->cachedTTL,
            true
        );
    }

    public function homeCategoryBySlug(string $slug): Category
    {
        return $this->categoryCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE_HOME,
            $slug,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function homeTopEvents(): Collection
    {
        return $this->eventCacheService->getRecordsByPosition(
            CacheHelper::KEY_PAGE_HOME,
            EventHelper::POSITION_TOP,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function homeBottomEvents(): Collection
    {
        return $this->eventCacheService->getRecordsByPosition(
            CacheHelper::KEY_PAGE_HOME,
            EventHelper::POSITION_BOTTOM,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function homeLeadNews(): Collection
    {
        return $this->newsCacheService->getRecordsLimitAccrodingNewsPlacement(
            CacheHelper::KEY_PAGE_HOME,
            PageHelper::PAGE_HOME,
            PageHelper::PAGE_SECTION_LEAD_NEWS,
            null,
            $this->language(),
            10,
            $this->cachedTTL
        );
    }

    public function homeEventNews(Event $event): Collection
    {
        return $this->newsCacheService->getRecordsLimit(
            CacheHelper::KEY_PAGE_HOME,
            null,
            $event,
            $this->language(),
            10,
            $this->cachedTTL
        );
    }

    public function homeCategoryNews(Request $request, Category $category): Collection
    {
        return $this->newsCacheService->getRecordsLimitAccrodingNewsPlacement(
            CacheHelper::KEY_PAGE_HOME,
            PageHelper::PAGE_HOME,
            PageHelper::PAGE_SECTION_CATEGORY_NEWS,
            $category,
            $this->language(),
            $request->input('limit', 4),
            $this->cachedTTL
        );
    }

    public function homeNewsTypeNews(NewsType $newsType): Collection
    {
        return $this->newsCacheService->getRecordsLimit(
            CacheHelper::KEY_PAGE_HOME,
            null,
            $newsType,
            $this->language(),
            10,
            $this->cachedTTL
        );
    }

    public function homeTrends(): Collection
    {
        return $this->tagCacheService->getRecordsLimitForTrend(
            CacheHelper::KEY_PAGE_HOME,
            $this->language(),
            15,
            $this->cachedTTL
        );
    }

    public function homeSurveys(): Collection
    {
        return $this->surveyCacheService->getRecordsByDate(
            CacheHelper::KEY_PAGE_HOME,
            now()->toDateString(),
            $this->language(),
            $this->cachedTTL
        );
    }

    public function homeSurvey(string $slug): Survey
    {
        return $this->surveyCacheService->getSurveyBySlug(
            CacheHelper::KEY_PAGE_HOME,
            $slug,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function homeSurveyQuestion(Survey $survey, string $slug): SurveyQuestion
    {
        return $this->surveyCacheService->getSurveyQuestionByQuestion(
            CacheHelper::KEY_PAGE_HOME,
            $survey,
            $slug,
            $this->language(),
            $this->cachedTTL
        );
    }

    public function homeSurveySurveyQuestionSubmit(Request $request, Survey $survey, SurveyQuestion $surveyQuestion): array
    {
        $yes = $request->boolean('yes');
        $no = $request->boolean('no');
        $noComment = $request->boolean('no_comment');

        if (! $yes && ! $no && ! $noComment) {
            return [
                'status' => 'warning',
                'message' => __(
                    'status-messages.site.survey.survey-question.no_answer_selected_warning'
                ),
                'data' => null,
            ];
        }

        $answer = null;

        if ($yes) {
            $answer = 'yes';
        } elseif ($no) {
            $answer = 'no';
        } else {
            $answer = 'no_comment';
        }

        $sessionKey = "survey.{$survey->id}.question.{$surveyQuestion->id}";

        $previousData = session()->get($sessionKey);

        try {

            DB::transaction(function () use ($surveyQuestion, $answer, $previousData, $sessionKey, $survey) {

                $surveyQuestionResult = SurveyQuestionResult::query()->firstOrCreate(
                    [
                        'survey_question_id' => $surveyQuestion->id,
                    ],
                    [
                        'yes' => 0,
                        'no' => 0,
                        'no_comment' => 0,
                    ]);

                $previousAnswer = $previousData['answer'] ?? null;

                if ($previousAnswer && $previousAnswer !== $answer) {

                    if ($previousAnswer === 'yes' && $surveyQuestionResult->yes > 0) {
                        $surveyQuestionResult->decrement('yes');
                    }

                    if ($previousAnswer === 'no' && $surveyQuestionResult->no > 0) {
                        $surveyQuestionResult->decrement('no');
                    }

                    if ($previousAnswer === 'no_comment' && $surveyQuestionResult->no_comment > 0) {
                        $surveyQuestionResult->decrement('no_comment');
                    }
                }

                if ($previousAnswer !== $answer) {
                    $surveyQuestionResult->increment($answer);
                }

                session()->put(
                    $sessionKey,
                    [
                        'survey_id' => $survey->id,
                        'survey_question_id' => $surveyQuestion->id,
                        'answer' => $answer,
                        'expired_at' => now()->addHours(6)->timestamp,
                    ]
                );
            });

            return [
                'status' => 'success',
                'message' => __('status-messages.site.survey.survey-question.success'),
                'data' => session()->get($sessionKey),
            ];

        } catch (Exception $exception) {

            Log::error('Failed to submit survey question.', ['exception' => $exception]);

            return [
                'status' => 'error',
                'message' => __('status-messages.site.survey.survey-question.fail'),
                'data' => null,
            ];
        }

    }

    public function newsHitCounterCalculate(News $news): void
    {
        $sessionKey = "news_hit_counted_{$news->id}";

        if (! session()->has($sessionKey)) {
            $news->increment('hit_count');

            session()->put($sessionKey, true);
        }
    }

    public function recentNewsSidebar(): Collection
    {
        return $this->newsCacheService->getLatestRecord(
            CacheHelper::KEY_PAGE,
            $this->language(),
            15,
            false,
            $this->cachedTTL
        );
    }

    public function popularNewsSidebar(): Collection
    {
        return $this->newsCacheService->getPopulerRecord(
            CacheHelper::KEY_PAGE,
            $this->language(),
            15,
            $this->cachedTTL
        );
    }
}
