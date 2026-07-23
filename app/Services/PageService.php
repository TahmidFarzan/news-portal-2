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
use App\Services\SiteService;
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
        $this->siteService             = $siteService;
        $this->newsCacheService        = $newsCacheService;
        $this->categoryCacheService    = $categoryCacheService;
        $this->tagCacheService         = $tagCacheService;
        $this->eventCacheService       = $eventCacheService;
        $this->contributorCacheService = $contributorCacheService;
        $this->pageCacheService        = $pageCacheService;
        $this->newsTypeCacheService    = $newsTypeCacheService;
        $this->locationCacheService    = $locationCacheService;
        $this->surveyCacheService      = $surveyCacheService;
    }

    public function language(string $code): Language
    {
        return $this->siteService->language($code);
    }

    public function defaultLanguage(): Language
    {
        return $this->siteService->defaultLanguage();
    }

    public function page(Language $language, string $slugTree): Page
    {
        return $this->pageCacheService->getRecordBySlugTree(
            CacheHelper::KEY_PAGE,
            $language,
            $slugTree,
            $this->cachedTTL
        );
    }

    public function homePage(Language $language): Page
    {
        return $this->pageCacheService->getRecordByUseAs(
            CacheHelper::KEY_PAGE,
            $language,
            PageHelper::DAFAULT_USE_AS_HOME,
            $this->cachedTTL
        );
    }

    public function latestPage(Language $language): Page
    {
        return $this->pageCacheService->getRecordByUseAs(
            CacheHelper::KEY_PAGE,
            $language,
            PageHelper::DAFAULT_USE_AS_LATEST,
            $this->cachedTTL
        );
    }

    public function searchPage(Language $language): Page
    {
        return $this->pageCacheService->getRecordByUseAs(
            CacheHelper::KEY_PAGE,
            $language,
            PageHelper::DAFAULT_USE_AS_SEARCH,
            $this->cachedTTL
        );
    }

    public function newsType(string $slug): NewsType
    {
        return $this->newsTypeCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $slug,
            $this->cachedTTL
        );
    }

    public function category(Language $language, string $slugTree): Category
    {
        return $this->categoryCacheService->getRecordBySlugTree(
            CacheHelper::KEY_PAGE,
            $language,
            $slugTree,

            $this->cachedTTL
        );
    }

    public function categoryById(Language $language, string $id): Category
    {
        return $this->categoryCacheService->getRecordById(
            CacheHelper::KEY_PAGE,
            $language,
            $id,

            $this->cachedTTL
        );
    }

    public function categoryBySlug(Language $language, string $slug): Category
    {
        return $this->categoryCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $language,
            $slug,
            $this->cachedTTL
        );
    }

    public function event(Language $language, string $slug): Event
    {
        return $this->eventCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $language,
            $slug,
            $this->cachedTTL
        );
    }

    public function tag(Language $language, string $slug): Tag
    {
        return $this->tagCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $language,
            $slug,
            $this->cachedTTL
        );
    }

    public function contributor(Language $language, string $slug): Contributor
    {
        return $this->contributorCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $language,
            $slug,
            $this->cachedTTL
        );
    }

    public function location(Language $language, string $slugTree): Location
    {
        return $this->locationCacheService->getRecordBySlugTree(
            CacheHelper::KEY_PAGE,
            $language,
            $slugTree,

            $this->cachedTTL
        );
    }

    public function locationById(Language $language, string $id): Location
    {
        return $this->locationCacheService->getRecordById(
            CacheHelper::KEY_PAGE,
            $language,
            $id,

            $this->cachedTTL
        );
    }

    public function news(Language $language, string $slug): News
    {
        return $this->newsCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE,
            $language,
            $slug,

            $this->cachedTTL
        );
    }

    public function categoryLocationMaxDepthAndLevel(Language $language, Category $category): object
    {
        return $this->locationCacheService->getMaxDepthAndLevel(
            CacheHelper::KEY_PAGE,
            $language,
            $category,

            $this->cachedTTL
        );
    }

    public function categoryNewsPlacement(Language $language, Category $category): Collection
    {
        return $this->newsCacheService->getRecordsLimitAccrodingNewsPlacement(
            CacheHelper::KEY_PAGE,
            $language,
            PageHelper::PAGE_CATEGORY,
            PageHelper::PAGE_SECTION_LEAD_NEWS,
            $category,

            5,
            $this->cachedTTL
        );
    }

    public function recentNews(Language $language): CursorPaginator
    {
        return $this->newsCacheService->getLatestRecord(
            CacheHelper::KEY_PAGE,
            $language,
            15,
            true,
            $this->cachedTTL
        );
    }

    public function popularNews(Language $language): Collection
    {
        return $this->newsCacheService->getPopulerRecord(
            CacheHelper::KEY_PAGE,
            $language,
            15,
            $this->cachedTTL
        );
    }

    public function newsTypeNews(Request $request, NewsType $newsType, Language $language): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $language,
            $request,
            $newsType,

            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function categoryNews(Request $request, Language $language, Category $category): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $language,
            $request,
            $category,

            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function eventNews(Request $request, Event $event, Language $language): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $language,
            $request,
            $event,

            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function locationNews(Request $request, Location $location, Language $language): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $language,
            $request,
            $location,

            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function tagNews(Request $request, Tag $tag, Language $language): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $language,
            $request,
            $tag,

            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function contributorNews(Request $request, Contributor $contributor, Language $language): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $language,
            $request,
            $contributor,

            $request->input('per_page', 24),
            $this->cachedTTL,
            true
        );
    }

    public function newsSearch(Request $request, Language $language): CursorPaginator
    {
        return $this->newsCacheService->getRecords(
            CacheHelper::KEY_PAGE,
            $language,
            $request,
            null,

            $request->input('per_page', 16),
            $this->cachedTTL,
            true
        );
    }

    public function homeCategoryBySlug(Language $language, string $slug): Category
    {
        return $this->categoryCacheService->getRecordBySlug(
            CacheHelper::KEY_PAGE_HOME,
            $language,
            $slug,
            $this->cachedTTL
        );
    }

    public function homeTopEvents(Language $language): Collection
    {
        return $this->eventCacheService->getRecordsByPosition(
            CacheHelper::KEY_PAGE_HOME,
            $language,
            EventHelper::POSITION_TOP,
            $this->cachedTTL
        );
    }

    public function homeBottomEvents(Language $language): Collection
    {
        return $this->eventCacheService->getRecordsByPosition(
            CacheHelper::KEY_PAGE_HOME,
            $language,
            EventHelper::POSITION_BOTTOM,
            $this->cachedTTL
        );
    }

    public function homeLeadNews(Language $language): Collection
    {
        return $this->newsCacheService->getRecordsLimitAccrodingNewsPlacement(
            CacheHelper::KEY_PAGE_HOME,
            $language,
            PageHelper::PAGE_HOME,
            PageHelper::PAGE_SECTION_LEAD_NEWS,
            null,
            10,
            $this->cachedTTL
        );
    }

    public function homeEventNews(Event $event, Language $language): Collection
    {
        return $this->newsCacheService->getRecordsLimit(
            CacheHelper::KEY_PAGE_HOME,
            $language,
            null,
            $event,

            10,
            $this->cachedTTL
        );
    }

    public function homeCategoryNews(Request $request, Language $language, Category $category): Collection
    {
        return $this->newsCacheService->getRecordsLimitAccrodingNewsPlacement(
            CacheHelper::KEY_PAGE_HOME,
            $language,
            PageHelper::PAGE_HOME,
            PageHelper::PAGE_SECTION_CATEGORY_NEWS,
            $category,

            $request->input('limit', 4),
            $this->cachedTTL
        );
    }

    public function homeNewsTypeNews(NewsType $newsType, Language $language): Collection
    {
        return $this->newsCacheService->getRecordsLimit(
            CacheHelper::KEY_PAGE_HOME,
            $language,
            null,
            $newsType,

            10,
            $this->cachedTTL
        );
    }

    public function homeTrends(Language $language): Collection
    {
        return $this->tagCacheService->getRecordsLimitForTrend(
            CacheHelper::KEY_PAGE_HOME,
            $language,
            15,
            $this->cachedTTL
        );
    }

    public function homeSurveys(Language $language): Collection
    {
        return $this->surveyCacheService->getRecordsByDate(
            CacheHelper::KEY_PAGE_HOME,
            $language,
            now()->toDateString(),
            $this->cachedTTL
        );
    }

    public function homeSurvey(Language $language, string $slug): Survey
    {
        return $this->surveyCacheService->getSurveyBySlug(
            CacheHelper::KEY_PAGE_HOME,
            $language,
            $slug,

            $this->cachedTTL
        );
    }

    public function homeSurveyQuestion(Language $language, Survey $survey, string $slug): SurveyQuestion
    {
        return $this->surveyCacheService->getSurveyQuestionByQuestion(
            CacheHelper::KEY_PAGE_HOME,
            $language,
            $survey,
            $slug,

            $this->cachedTTL
        );
    }

    public function homeSurveySurveyQuestionSubmit(Request $request, Survey $survey, SurveyQuestion $surveyQuestion): array
    {
        $yes       = $request->boolean('yes');
        $no        = $request->boolean('no');
        $noComment = $request->boolean('no_comment');

        if (! $yes && ! $no && ! $noComment) {
            return [
                'status'  => 'warning',
                'message' => __(
                    'status-messages.site.survey.survey-question.no_answer_selected_warning'
                ),
                'data'    => null,
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
                        'yes'        => 0,
                        'no'         => 0,
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
                        'survey_id'          => $survey->id,
                        'survey_question_id' => $surveyQuestion->id,
                        'answer'             => $answer,
                        'expired_at'         => now()->addHours(6)->timestamp,
                    ]
                );
            });

            return [
                'status'  => 'success',
                'message' => __('status-messages.site.survey.survey-question.success'),
                'data'    => session()->get($sessionKey),
            ];

        } catch (Exception $exception) {

            Log::error('Failed to submit survey question.', ['exception' => $exception]);

            return [
                'status'  => 'error',
                'message' => __('status-messages.site.survey.survey-question.fail'),
                'data'    => null,
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

    public function recentNewsSidebar(Language $language): Collection
    {
        return $this->newsCacheService->getLatestRecord(
            CacheHelper::KEY_PAGE,
            $language,
            15,
            false,
            $this->cachedTTL
        );
    }

    public function popularNewsSidebar(Language $language): Collection
    {
        return $this->newsCacheService->getPopulerRecord(
            CacheHelper::KEY_PAGE,
            $language,
            15,
            $this->cachedTTL
        );
    }
}
