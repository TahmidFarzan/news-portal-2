<?php
namespace App\Services;

use App\Helpers\CacheServerHelper;
use App\Helpers\GoogleAdsenceHelper;
use App\Helpers\MenuHelper;
use App\Helpers\ThemeHelper;
use App\Models\BreakingNews;
use App\Models\GoogleAdsence;
use App\Models\Language;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Survey;
use App\Models\SurveyQuestion;
use App\Models\SurveyQuestionResult;
use App\Models\Theme;
use App\Models\Trend;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SiteService
{
    public function language(): Language
    {
        $languageId = session('selected_language_id');

        if ($languageId) {
            return Language::select([
                'id',
                'name',
                'code',
                "locale",
                'slug',
            ])
                ->where('id', $languageId)
                ->firstOrFail();
        }

        return Language::select([
            'id',
            'name',
            'code',
            "locale",
            'slug',
        ])
            ->oldest('id')
            ->firstOrFail();
    }

    public function menuItem(string $slug): MenuItem
    {
        return MenuItem::select([
            'id',
            'name',
            'slug',
            'language_id',
            'model_type',
            'model_id',
            'menu_id',
        ])
            ->with([
                'model:id,slug',
                'language:id,name,code,locale,slug',
                'menu:id,name,language_id,slug,menu_type_id',
                'menu.menuType:id,name,slug',
            ])
            ->where('slug', $slug)
            ->firstOrFail();
    }

    public function menuHeaderMenuMenuItems(Request $request): array
    {
        $perPage = 10;
        $page    = max((int) $request->input('page', 1), 1);

        $language          = $this->language();
        $headerMenuCode    = MenuHelper::MENU_TYPE_HEADER;
        $headerMenuCodeKey = Str::lower($headerMenuCode);

        $cacheKey = "site:language:{$language->locale}:{$headerMenuCodeKey}:menu-items:per-page:{$perPage}:page:{$page}";

        $cacheTags = [
            "site",
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:{$headerMenuCodeKey}",
            "site:language:{$language->locale}:{$headerMenuCodeKey}:menu-items",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $menu = Menu::with(['menuType'])->select(["id", "name", 'slug', "language_id"])->where("language_id", $language->id)->whereRelation('menuType', 'name', $headerMenuCode)->firstOrFail();

        $query = MenuItem::query()
            ->with([
                'model',
            ])
            ->select([
                'id',
                'name',
                "slug",
                "model_type",
                "model_id",
                'position',
            ])
            ->whereNull("parent_id")
            ->where('menu_id', $menu->id)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        $list = $query->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
            'public_url'      => $row->public_url,
        ]);

        $data = [
            'items'        => $list,
            'total'        => $query->total(),
            'current_page' => $query->currentPage(),
            'last_page'    => $query->lastPage(),
            'per_page'     => $query->perPage(),
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function menuOffCanvasMenuMenuItems(Request $request): array
    {
        $perPage = 20;
        $page    = max((int) $request->input('page', 1), 1);

        $language             = $this->language();
        $offcanvasMenuCode    = MenuHelper::MENU_TYPE_OFFCANVAS;
        $offcanvasMenuCodeKey = Str::lower($offcanvasMenuCode);

        $cacheKey = "site:language:{$language->locale}:{$offcanvasMenuCodeKey}:menu-items:per-page:{$perPage}:page:{$page}";

        $cacheTags = [
            "site",
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:{$offcanvasMenuCodeKey}",
            "site:language:{$language->locale}:{$offcanvasMenuCodeKey}:menu-items",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }
        $menu = Menu::with(['menuType'])->select(["id", "name", 'slug', "language_id"])->where("language_id", $language->id)->whereRelation('menuType', 'name', $offcanvasMenuCode)->firstOrFail();

        $query = MenuItem::query()
            ->with([
                'model',
            ])
            ->select([
                'id',
                'name',
                "slug",
                "model_type",
                "model_id",
                'position',
            ])
            ->whereNull("parent_id")
            ->where('menu_id', $menu->id)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        $list = $query->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
            'public_url'      => $row->public_url,
        ]);

        $data = [
            'items'        => $list,
            'total'        => $query->total(),
            'current_page' => $query->currentPage(),
            'last_page'    => $query->lastPage(),
            'per_page'     => $query->perPage(),
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function menuTopbarMenuMenuItems(Request $request): array
    {
        $perPage = 20;
        $page    = max((int) $request->input('page', 1), 1);

        $language          = $this->language();
        $topbarMenuCode    = MenuHelper::MENU_TYPE_TOPBAR;
        $topbarMenuCodeKey = Str::lower($topbarMenuCode);

        $cacheKey = "site:language:{$language->locale}:{$topbarMenuCodeKey}:menu-items:per-page:{$perPage}:page:{$page}";

        $cacheTags = [
            "site",
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:{$topbarMenuCodeKey}",
            "site:language:{$language->locale}:{$topbarMenuCodeKey}:menu-items",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $menu = Menu::with(['menuType'])->select(["id", "name", 'slug', "language_id"])->where("language_id", $language->id)->whereRelation('menuType', 'name', $topbarMenuCode)->firstOrFail();

        $query = MenuItem::query()
            ->with([
                'model',
            ])
            ->select([
                'id',
                'name',
                "slug",
                "model_type",
                "model_id",
                'position',
            ])
            ->whereNull("parent_id")
            ->where('menu_id', $menu->id)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        $list = $query->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
            'public_url'      => $row->public_url,
        ]);

        $data = [
            'items'        => $list,
            'total'        => $query->total(),
            'current_page' => $query->currentPage(),
            'last_page'    => $query->lastPage(),
            'per_page'     => $query->perPage(),
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function menuFooterMenuMenuItems(Request $request): array
    {
        $perPage = 20;
        $page    = max((int) $request->input('page', 1), 1);

        $language          = $this->language();
        $footerMenuCode    = MenuHelper::MENU_TYPE_FOOTER;
        $footerMenuCodeKey = Str::lower($footerMenuCode);

        $cacheKey = "site:language:{$language->locale}:{$footerMenuCodeKey}:menu-items:per-page:{$perPage}:page:{$page}";

        $cacheTags = [
            "site",
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:{$footerMenuCodeKey}",
            "site:language:{$language->locale}:{$footerMenuCodeKey}:menu-items",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $menu = Menu::with(['menuType'])->select(["id", "name", 'slug', "language_id"])->where("language_id", $language->id)->whereRelation('menuType', 'name', $footerMenuCode)->firstOrFail();

        $query = MenuItem::query()
            ->with([
                'model',
            ])
            ->select([
                'id',
                'name',
                "slug",
                "model_type",
                "model_id",
                'position',
            ])
            ->whereNull("parent_id")
            ->where('menu_id', $menu->id)
            ->whereRelation('menu.language', 'id', $language->id)
            ->whereRelation('menu.menuType', 'name', $footerMenuCode)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        $list = $query->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
            'public_url'      => $row->public_url,
        ]);

        $data = [
            'items'        => $list,
            'total'        => $query->total(),
            'current_page' => $query->currentPage(),
            'last_page'    => $query->lastPage(),
            'per_page'     => $query->perPage(),
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function menuItemSubMenuItems(Request $request, MenuItem $menuItem): array
    {
        $perPage = 10;
        $page    = max((int) $request->input('page', 1), 1);

        $language = $menuItem->language;

        $menu     = $menuItem->menu;
        $menuType = $menu->menuType;

        $cacheKey = "site:language:{$language->locale}:menu-type:{$menuType->slug}:menu:{$menu->slug}:menu-item:{$menuItem->slug}:menu-items:per-page:{$perPage}:page:{$page}";

        $cacheTags = [
            'site',
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:menu-type:{$menuType->slug}",
            "site:language:{$language->locale}:menu-type:{$menuType->slug}:menu:{$menu->slug}",
            "site:language:{$language->locale}:menu-type:{$menuType->slug}:menu:{$menu->slug}:menu-item:{$menuItem->slug}",
            "site:language:{$language->locale}:menu-type:{$menuType->slug}:menu:{$menu->slug}:menu-item:{$menuItem->slug}:menu-items",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $records = MenuItem::query()
            ->with([
                'model',
            ])
            ->select([
                'id',
                'name',
                "slug",
                "model_type",
                "model_id",
                'position',
            ])
            ->where('parent_id', $menuItem->id)
            ->where('language_id', $menuItem->language_id)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'asc')
            ->paginate($perPage);

        $list = $records->getCollection()->map(fn($row) => [
            'id'              => $row->id,
            'name'            => $row->name,
            'slug'            => $row->slug,
            'parent'          => $row->parent,
            'has_descendants' => $row->has_descendants,
            'public_url'      => $row->public_url,
        ]);

        $data = [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
            'per_page'     => $records->perPage(),
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function themes()
    {
        $cacheKey = 'site:themes';

        $cacheTags = [
            'site',
            'site:themes',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $data = Theme::select([
            'group',
            'label',
            'type',
            'value',
            'slug',
        ])
            ->orderBy('id', 'asc')
            ->get();

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function themeHeader()
    {
        $cacheKey = 'site:theme:header';

        $cacheTags = [
            'site',
            'site:theme',
            'site:theme:header',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }
        $labels = [
            ThemeHelper::OPTION_GOOGLE_SEARCH_CONSOLE_HEADER,
            ThemeHelper::OPTION_GOOGLE_ANALYTIC_HEADER,
            ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_HEADER,
        ];

        $data = Theme::select([
            'group',
            'label',
            'type',
            'value',
            'slug',
        ])->where('group', ThemeHelper::GROUP_APP)->whereIn('label', $labels)->get();

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function themeBody()
    {
        $cacheKey = 'site:theme:body';

        $cacheTags = [
            'site',
            'site:theme',
            'site:theme:body',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $labels = [
            ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_BODY,
        ];

        $data = Theme::select([
            'group',
            'label',
            'type',
            'value',
            'slug',
        ])->where('group', ThemeHelper::GROUP_APP)->whereIn('label', $labels)->get();

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function themeGoogleAdCLientId()
    {
        $cacheKey = 'site:theme:google:ad-client-id';

        $cacheTags = [
            'site',
            'site:theme',
            'site:theme:google',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $data = Theme::select([
            'group',
            'label',
            'type',
            'value',
            'slug',
        ])->where('group', ThemeHelper::GROUP_APP)->where('label', ThemeHelper::OPTION_GOOGLE_ADSENCE_CLIENT_ID)->firstOrFail();

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function breakingNews(Request $request)
    {
        $perPage = 10;

        $cursor    = $request->input('cursor');
        $cursorKey = $cursor ? md5($cursor) : 'first';

        $language = $this->language();

        $cacheKey = "site:language:{$language->locale}:breaking-news:cursor:{$cursorKey}:per-page:{$perPage}";

        $cacheTags = [
            'site',
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:breaking-news",
            "site:language:{$language->locale}:breaking-news",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $query = BreakingNews::
            select([
            "id",
            "title",
            "slug",
            "language_id",
            "news_id",
        ])
            ->with(
                "news:id,title,slug,language_id,category_id,news_type_id,created_at,updated_at",
                "news.category:id,name,name_tree,slug,slug_tree",
            )
            ->where('is_published', true)
            ->where('language_id', $language->id)
            ->whereRelation('news', 'language_id', $language->id)
            ->orderByDesc('created_at')
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->cursorPaginate($perPage, ['*'], 'cursor');

        CacheServerHelper::cachedData(
            $cacheKey,
            $query,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $query;
    }

    public function languages(Request $request)
    {
        $perPage = (int) $request->input('per_page', 15);
        $search  = $request->input('search');
        $page    = (int) $request->input('page', 1);

        $cacheSearch = md5($search ?? '');

        $cacheKey = "site:languages:basic-pagination:search:{$cacheSearch}:page:{$page}:per-page:{$perPage}";

        $cacheTags = [
            'site',
            'site.languages',
            'site:languages:basic-pagination',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $query = Language::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $records = $query
            ->orderByDesc('id')
            ->paginate($perPage);

        $list = $records->map(fn($row) => [
            'id'   => $row->id,
            'name' => $row->name,
            'slug' => $row->slug,
        ]);

        $data = [
            'items'        => $list,
            'total'        => $records->total(),
            'current_page' => $records->currentPage(),
            'last_page'    => $records->lastPage(),
            'per_page'     => $records->perPage(),
        ];

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function languageChange(int | string $slugOrId): array
    {
        $language = Language::query()
            ->where('id', $slugOrId)
            ->orWhere('slug', $slugOrId)
            ->firstOrFail();

        session()->put('selected_language_id', $language->id);

        return [
            'status'  => true,
            'message' => __('status-messages.site.language.change.success'),
            'data'    => $language,
        ];
    }

    public function getGoogleAdsence(Request $request)
    {
        $type     = $request->input("type", GoogleAdsenceHelper::TYPE_SECTION);
        $position = $request->input("position", GoogleAdsenceHelper::POSITION_TOP);

        $typeCacheKey     = Str::lower($type);
        $positionCacheKey = Str::lower($position);

        $cacheKey = "site:google-adsence:type:{$typeCacheKey}:position:{$positionCacheKey}";

        $cacheTags = [
            'site',
            'site:google-adsence',
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $data = GoogleAdsence::select([
            "id",
            'name',
            'slug',
            "slot_id",
            "client_id",
            "type",
            "position",
        ])->where('type', $type)->where('position', $position)->get();

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function trends()
    {
        $language = $this->language();

        $cacheKey = "site:language:{$language->locale}:trends";

        $cacheTags = [
            "site",
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:trends",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $data = Trend::select([
            "id", 'tag_id', 'is_current',
        ])
            ->with([
                "tag:id,name,slug,language_id",
            ])
            ->where("is_current", true)
            ->whereRelation('tag', 'language_id', $language->id)
            ->orderBy('position', 'asc')
            ->orderBy('id', 'desc')
            ->limit(15)
            ->get();

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function surveys()
    {
        $language = $this->language();

        $cacheKey = "site:language:{$language->locale}:trends";

        $cacheTags = [
            "site",
            "site:language:{$language->locale}",
            "site:language:{$language->locale}:trends",
        ];

        $cachedData = CacheServerHelper::getCachedData($cacheKey, $cacheTags);

        if ($cachedData !== null) {
            return $cachedData;
        }

        $data = Survey::select(["id","name","slug",'date',])
            ->with([
                "surveyQuestions:id,survey_id,question,slug",
                "surveyQuestions.surveyQuestionResult:id,survey_question_id,yes,no,no_comment",
            ])
            ->where("is_active", true)
            ->where('language_id', $language->id)
            ->whereDate('date', now()->toDateString())
            ->orderBy('id', 'desc')
            ->get();

        CacheServerHelper::cachedData(
            $cacheKey,
            $data,
            CacheServerHelper::sixHoursInSecond,
            $cacheTags
        );

        return $data;
    }

    public function survey(string $slug): Survey
    {
        return Survey::select(["id", 'name', 'brief', 'slug', 'date', "language_id"])->where('slug', $slug)->firstOrFail();
    }

    public function surveyQuestion(Survey $survey, string $slug): SurveyQuestion
    {
        return SurveyQuestion::select(["id", 'question', 'survey_id', 'slug',])
            ->where('survey_id', $survey->id)->where('slug', $slug)->firstOrFail();
    }

    public function surveySurveyQuestionSubmit(Request $request, Survey $survey, SurveyQuestion $surveyQuestion): array
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

}
