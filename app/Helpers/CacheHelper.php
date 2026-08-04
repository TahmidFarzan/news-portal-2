<?php

namespace App\Helpers;

use App\Models\Category;
use App\Models\Contributor;
use App\Models\Event;
use App\Models\Language;
use App\Models\Location;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\NewsType;
use App\Models\Survey;
use App\Models\Quiz;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CacheHelper
{
    public const KEY_SITE = 'site';

    public const KEY_LAYOUT = 'layout';

    public const KEY_PAGE = 'page';

    public const KEY_THEME = 'theme';

    public const KEY_THEMES = 'themes';

    public const KEY_PAGE_HOME = 'home-page';

    public const KEY_GOOGLE_ADSENCE = 'google-adsence';

    public const KEY_PAGE_NAME = 'page-name';

    public const KEY_PAGE_SECTION_NAME = 'page-section-name';

    public const KEY_PAGE_SECTION_CATEGORY = 'category';

    public const KEY_PAGE_SECTION_VIDEO = 'video';

    public const KEY_PAGE_SECTION_IMAGE_GALLERY = 'image-gallery';

    public const KEY_MENU = 'menu';

    public const KEY_SITEMAP = 'sitemap';

    public const KEY_FEED = 'feed';

    public const KEY_NEWS = 'news';

    public const KEY_CATEGORY = 'category';

    public const KEY_NEWS_TYPE = 'news-type';

    public const KEY_TAG = 'tag';

    public const KEY_TREND = 'trend';

    public const KEY_EVENT = 'event';

    public const KEY_SURVEY = 'survey';

    public const KEY_SURVEY_QUESTION = 'survey-question';

    public const KEY_QUIZ = 'quiz';
    public const KEY_PREVIOUS_QUIZ = 'previous-quiz';

    public const KEY_QUIZ_QUESTION = 'quiz-question';

    public const KEY_LOCATION = 'location';

    public const KEY_CONTRIBUTOR = 'contributor';

    public const KEY_LANGUAGE = 'language';

    public const KEY_DATE = 'date';

    public const KEY_MENU_ITEM = 'menu-item';

    public const KEY_COUNT = 'count';

    public const KEY_LATEST = 'latest';

    public const KEY_POPULER = 'populer';

    public const KEY_LAST_PAGE_NO = 'last-page-no';

    public const KEY_PAGE_NO = 'page-no';

    public const KEY_LIMIT = 'limit';

    public const KEY_PER_PAGE = 'per-page';

    public const KEY_GROUP = 'group';

    public const KEY_LABEL = 'label';

    public const KEY_TYPE = 'type';

    public const KEY_POSITION = 'position';

    public const KEY_BREAKING_NEWS = 'breaking-news';

    public const KEY_CURSOR = 'cursor';

    public const KEY_BY_ID = 'by:id';

    public const KEY_USE_AS = 'by:use-as';

    public const KEY_BY_SLUG = 'by:slug';
    public const KEY_BY_DATE = 'by:date';

    public const KEY_BY_SLUG_TREE = 'by:slug-tree';

    public const KEY_BY_MENU_TYPE_CODE = 'by:menu-type-code';

    public const KEY_BY_DEFAULT = 'by:default';

    public const KEY_MAX_DEPTH_AND_LEVEL = 'max-depth-and-level';

    public const KEY_QUIZ_WINNER_RESULTS = 'quiz-winner-results';

    public const TAG_PAGE = 'page';

    public const TAG_SITEMAP = 'sitemap';

    public const TAG_FEED = 'feed';

    public const TAG_NEWS = 'news';

    public const TAG_CATEGORY = 'category';

    public const TAG_NEWS_TYPE = 'news-type';

    public const TAG_TAG = 'tag';

    public const TAG_EVENT = 'event';

    public const TAG_LOCATION = 'location';

    public const TAG_CONTRIBUTOR = 'contributor';

    public const TAG_SURVEY = 'survey';

    public const TAG_SURVEY_QUESTION = 'survey-question';

    public const TAG_QUIZ = 'quiz';

    public const TAG_QUIZ_QUESTION = 'quiz-question';

    public const TAG_MENU = 'menu';

    public const TAG_THEME = 'theme';

    public const TAG_GOOGLE_ADSENCE = 'google-adsence';

    public const TAG_LANGUAGE = 'language';

    public static function cacheKeyGenerateSingleRecordById(string $key, string $secondKey, string | int $id, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}:";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_BY_ID . ":{$id}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleRecordBySlug(string $key, string $secondKey, string | int $slug, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}:";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_BY_SLUG . ":{$slug}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleRecordByCode(string $key, string $secondKey, string | int $slug, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}:";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_BY_SLUG . ":{$slug}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleRecordBySlugTree(string $key, string $secondKey, string | int $slugTree, ?Language $language = null): string
    {
        $safeSlugTreeForCache = str_replace('/', '_', trim($slugTree, '/'));

        $cacheKey = "{$key}:{$secondKey}:";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_BY_SLUG_TREE . ":{$safeSlugTreeForCache}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleRecordByUseAs(string $key, string $secondKey, string $useAs, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}:";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_USE_AS . ":{$useAs}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleRecordByIdWithoutLanguage(string $key, string $secondKey, string | int $id): string
    {
        $cacheKey = "{$key}:{$secondKey}:";

        $cacheKey .= ':' . CacheHelper::KEY_BY_ID . ":{$id}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleRecordBySlugWithoutLanguage(string $key, string $secondKey, string | int $slug,): string
    {
        $cacheKey = "{$key}:{$secondKey}:";

        $cacheKey .= ':' . CacheHelper::KEY_BY_SLUG . ":{$slug}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleRecordByCodeWithoutLanguage(string $key, string $secondKey, string | int $slug): string
    {
        $cacheKey = "{$key}:{$secondKey}:";

        $cacheKey .= ':' . CacheHelper::KEY_BY_SLUG . ":{$slug}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleDefaultRecordByCodeWithoutLanguage(string $key, string $secondKey): string
    {
        $cacheKey = "{$key}:{$secondKey}:";

        $cacheKey .= ':' . CacheHelper::KEY_BY_DEFAULT;

        return $cacheKey;
    }

    public static function cacheKeyGenerateForRecordByLimit(string $key, string $secondKey, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?Request $request = null, ?Language $language = null, int $limit = 4): string
    {
        $request ??= request();
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($filterModel instanceof Category && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_CATEGORY . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Tag && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_TAG . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Event && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_EVENT . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Location && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LOCATION . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Contributor && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_CONTRIBUTOR . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof NewsType && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_NEWS_TYPE . ":{$filterModel?->slug}";
        }

        if ($request->input()) {
            $cacheData = $request->except([
                '_token',
            ]);

            ksort($cacheData);

            $cacheKey .= ':' . md5(json_encode($cacheData));
        }

        $cacheKey .= ':' . CacheHelper::KEY_LIMIT . ":{$limit}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateForRecordByLimitAccrodingNewsPlacement(string $key, string $secondKey, ?string $pageName = null, ?string $pageSection = null, ?Category $category = null, int $limit = 4, ?Language $language = null): string
    {
        $pageSectionSlugKey = Str::lower(Str::slug($pageSection));

        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($pageName) {
            $cacheKey .= ':' . CacheHelper::KEY_PAGE_NAME . ":{$pageName}";
        }

        if ($pageSection && $pageSectionSlugKey) {
            $cacheKey .= ':' . CacheHelper::KEY_PAGE_SECTION_NAME . ":{$pageSectionSlugKey}";
        }

        if ($category && $category?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_CATEGORY . ":{$category->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_LIMIT . ":{$limit}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateForLatest(string $key, string $secondKey, ?Language $language = null, bool $isCursorPaginate = false): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($isCursorPaginate) {
            $cacheKey .= ':' . CacheHelper::KEY_CURSOR;
        }

        $cacheKey .= ':' . CacheHelper::KEY_LATEST;

        return $cacheKey;
    }

    public static function cacheKeyGenerateForPopuler(string $key, string $secondKey, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_POPULER;

        return $cacheKey;
    }

    public static function cacheKeyGenerateForRecordPerPage(string $key, string $secondKey, string | int $page = 1, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_PAGE_NO . ":{$page}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateForRecordCount(string $key, string $secondKey, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_COUNT;

        return $cacheKey;
    }

    public static function cacheKeyGenerateForLastPageNo(string $key, string $secondKey, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_LAST_PAGE_NO;

        return $cacheKey;
    }

    public static function cacheKeyGenerateForMaxDepthAndLevel(string $key, string $secondKey, ?Category $category = null, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($category && $category?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_CATEGORY . ':' . $category->slug;
        }

        $cacheKey .= ':' . CacheHelper::KEY_MAX_DEPTH_AND_LEVEL;

        return $cacheKey;
    }

    public static function cacheKeyGenerateForNews(string $key, string $secondKey, ?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?Language $language = null, ?int $perPage = null): string
    {
        $request ??= request();
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($filterModel instanceof Category && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_CATEGORY . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Tag && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_TAG . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Event && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_EVENT . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Location && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LOCATION . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Contributor && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_CONTRIBUTOR . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof NewsType && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_NEWS_TYPE . ":{$filterModel?->slug}";
        }

        if ($perPage) {
            $cacheKey .= ':' . CacheHelper::KEY_PER_PAGE . ":{$perPage}";
        }

        if ($request->input()) {
            $cacheData = $request->except([
                '_token',
            ]);

            ksort($cacheData);

            $cacheKey .= ':' . md5(json_encode($cacheData));
        }

        return $cacheKey;
    }

    public static function cacheKeyGenerateForRecordsRequest(string $key, string $secondKey, Request $request, ?Language $language = null): string
    {
        $request ??= request();
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($request->input()) {
            $cacheData = $request->except([
                '_token',
            ]);

            ksort($cacheData);

            $cacheKey .= ':' . md5(json_encode($cacheData));
        }

        return $cacheKey;
    }

    public static function cacheKeyGenerateForRecordsRequestWithoutLanguage(string $key, string $secondKey, Request $request): string
    {
        $request ??= request();
        $cacheKey = "{$key}:{$secondKey}";

        if ($request->input()) {
            $cacheData = $request->except([
                '_token',
            ]);

            ksort($cacheData);

            $cacheKey .= ':' . md5(json_encode($cacheData));
        }

        return $cacheKey;
    }

    public static function cacheKeyGenerateForLastPageNoByFilter(string $key, string $secondKey, ?Request $request = null, NewsType | Category | Tag | Contributor | Event | Location | null $filterModel = null, ?Language $language = null): string
    {
        $request ??= request();

        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($filterModel instanceof Category && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_CATEGORY . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Tag && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_TAG . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Event && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_EVENT . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Location && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LOCATION . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof Contributor && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_CONTRIBUTOR . ":{$filterModel?->slug}";
        }

        if ($filterModel instanceof NewsType && $filterModel->id) {
            $cacheKey .= ':' . CacheHelper::KEY_NEWS_TYPE . ":{$filterModel?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_LAST_PAGE_NO;

        if ($request->input()) {
            $cacheData = $request->except([
                '_token',
            ]);

            ksort($cacheData);

            $cacheKey .= ':' . md5(json_encode($cacheData));
        }

        return $cacheKey;
    }

    public static function cacheKeyGenerateForEventByPosition(string $key, string $secondKey, string $position, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }
        $cacheKey .= ':' . CacheHelper::KEY_EVENT;

        $cacheKey .= ':' . CacheHelper::KEY_POSITION . ':' . Str::lower($position);

        return $cacheKey;
    }

    public static function cacheKeyGenerateForRecordsLimitForTrend(string $key, string $secondKey, ?Language $language = null, ?int $limit = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }
        $cacheKey .= ':' . CacheHelper::KEY_TREND;

        if ($limit) {
            $cacheKey .= ':' . CacheHelper::KEY_LIMIT . ":{$limit}";
        }

        return $cacheKey;
    }

    public static function cacheKeyGenerateSurveysByDate(string $key, string $secondKey, string $date, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";
        $cacheKey .= ':' . CacheHelper::KEY_SURVEY;

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($date) {
            $cacheKey .= ':' . CacheHelper::KEY_DATE . ":{$date}";
        }

        return $cacheKey;
    }

    public static function cacheKeyGenerateSurveyQuestionBySlugForSurvey(string $key, string $secondKey, Survey $survey, string $slug, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";
        $cacheKey .= ':' . CacheHelper::KEY_SURVEY;

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($survey) {
            $cacheKey .= ':' . CacheHelper::KEY_BY_SLUG . ":{$survey?->slug}";
        }

        if ($slug) {
            $cacheKey .= ':' . CacheHelper::KEY_SURVEY_QUESTION . ":{$slug}";
        }

        return $cacheKey;
    }

    public static function cacheKeyGenerateForQuizzesByDate(string $key, string $secondKey, string $date, ?Language $language = null, Request|null $request = null): string
    {
        $request ??= request();

        $cacheKey = "{$key}:{$secondKey}";
        $cacheKey .= ':' . CacheHelper::KEY_QUIZ;

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($date) {
            $cacheKey .= ':' . CacheHelper::KEY_DATE . ":{$date}";
        }
        if ($request) {
            $cacheData = $request->except([
                '_token',
            ]);

            ksort($cacheData);

            $cacheKey .= ':' . md5(json_encode($cacheData));
        }

        return $cacheKey;
    }

    public static function cacheKeyGenerateForQuizQuestionByQuiz(string $key, string $secondKey, Quiz $quiz, ?Language $language = null, ?Request $request = null,  int $perPage = 10): string
    {
        $request ??= request();

        $cacheKey = "{$key}:{$secondKey}";

        $cacheKey .= ':' . CacheHelper::KEY_SURVEY_QUESTION;

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }


        if ($quiz && $quiz?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_QUIZ . ":{$quiz?->slug}";
        }

        if ($request->input()) {
            $cacheData = $request->except([
                '_token',
            ]);

            ksort($cacheData);

            $cacheKey .= ':' . md5(json_encode($cacheData));
        }
        $cacheKey .= ':' . CacheHelper::KEY_PER_PAGE . ":{$perPage}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateForQuizWinnerResultsByQuiz(string $key, string $secondKey,  Quiz $quiz, ?Language $language = null,)
    {
        $cacheKey = "{$key}:{$secondKey}";
        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }
        $cacheKey .= ':' . CacheHelper::KEY_QUIZ .':' . CacheHelper::KEY_BY_SLUG . ":{$quiz->slug}";

        $cacheKey .= ':' . CacheHelper::KEY_QUIZ_WINNER_RESULTS;

        return $cacheKey;
    }

    public static function cacheKeyGenerateForPreviousQuiz(string $key, string $secondKey, string $nowDate, ?Language $language = null,)
    {
        $cacheKey = "{$key}:{$secondKey}";
        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_PREVIOUS_QUIZ .':' . CacheHelper::KEY_BY_DATE . ":{$nowDate}";


        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleRecordByMenuTypeCode(string $key, string $secondKey, string $menuTypeCode, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_BY_MENU_TYPE_CODE . ":{$menuTypeCode}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleMenuItemBySlug(string $key, string $secondKey, string $slug, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_MENU_ITEM;

        $cacheKey .= ':' . CacheHelper::KEY_BY_SLUG . ":{$slug}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateSingleMenuItems(string $key, string $secondKey, Menu $menu, ?MenuItem $menuItem = null, ?Language $language = null): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($menu && $menu?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_MENU . ":{$menu?->slug}";
        }

        if ($menuItem && $menuItem?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_MENU_ITEM . ":{$menuItem?->slug}";
        }

        $cacheKey .= ':' . CacheHelper::KEY_MENU_ITEM;

        return $cacheKey;
    }

    public static function cacheKeyGenerateThemes(string $key, string $secondKey): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        $cacheKey .= ':' . CacheHelper::KEY_THEMES;

        return $cacheKey;
    }

    public static function cacheKeyGenerateThemesByGroupAndLabels(string $key, string $secondKey, string $group, array $labels): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        $cacheKey .= ':' . CacheHelper::KEY_GROUP . ":{$group}";

        $cacheKey .= ':' . CacheHelper::KEY_LABEL . ':' . (
            is_array($labels) ? implode(',', $labels) : $labels
        );

        $cacheKey .= ':' . CacheHelper::KEY_THEMES;

        return $cacheKey;
    }

    public static function cacheKeyGenerateThemesByGroupAndLabel(string $key, string $secondKey, string $group, string $label): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        $cacheKey .= ':' . CacheHelper::KEY_GROUP . ":{$group}";
        $cacheKey .= ':' . CacheHelper::KEY_LABEL . ":{$label}";

        $cacheKey .= ':' . CacheHelper::KEY_THEME;

        return $cacheKey;
    }

    public static function cacheKeyGenerateForBreakingNews(string $key, string $secondKey, ?Request $request = null, ?Language $language = null, int $perPage = 10): string
    {
        $request ??= request();

        $cacheKey = "{$key}:{$secondKey}";

        $cacheKey .= ':' . CacheHelper::KEY_BREAKING_NEWS;

        if ($language && $language?->id) {
            $cacheKey .= ':' . CacheHelper::KEY_LANGUAGE . ":{$language?->slug}";
        }

        if ($request->input()) {
            $cacheData = $request->except([
                '_token',
            ]);

            ksort($cacheData);

            $cacheKey .= ':' . md5(json_encode($cacheData));
        }
        $cacheKey .= ':' . CacheHelper::KEY_PER_PAGE . ":{$perPage}";

        return $cacheKey;
    }

    public static function cacheKeyGenerateGoogleAdsencesByTypeAndPosition(string $key, string $secondKey, string $type, string $position): string
    {
        $cacheKey = "{$key}:{$secondKey}";

        $cacheKey .= ':' . CacheHelper::KEY_TYPE . ":{$type}";
        $cacheKey .= ':' . CacheHelper::KEY_POSITION . ":{$position}";

        $cacheKey .= ':' . CacheHelper::KEY_GOOGLE_ADSENCE;

        return $cacheKey;
    }
}
