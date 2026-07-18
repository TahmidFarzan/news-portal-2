<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BackOffice\ActivityLogController;

// Backoffice
use App\Http\Controllers\BackOffice\BreakingNewsController;
use App\Http\Controllers\BackOffice\CategoryController;
use App\Http\Controllers\BackOffice\ContributorController;
use App\Http\Controllers\BackOffice\EventController;
use App\Http\Controllers\BackOffice\GoogleAdsenceController;
use App\Http\Controllers\BackOffice\LocationController;
use App\Http\Controllers\BackOffice\MediaController;
use App\Http\Controllers\BackOffice\MenuController;
use App\Http\Controllers\BackOffice\NewsController;
use App\Http\Controllers\BackOffice\PageController as BackOfficePageController;
use App\Http\Controllers\BackOffice\SettingController;
use App\Http\Controllers\BackOffice\SurveyController;
use App\Http\Controllers\BackOffice\TagController;
use App\Http\Controllers\BackOffice\ThemeController;
use App\Http\Controllers\BackOffice\TrendController;
use App\Http\Controllers\BackOffice\UserController;

//
use App\Http\Controllers\FeedController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;
use romanzipp\QueueMonitor\Controllers\ShowQueueMonitorController;

Route::middleware('guest')->group(function () {
    Route::prefix('login')->group(function () {
        Route::get('/', [AuthController::class, 'loginForm'])->name('login'); // Default route name
        Route::post('/', [AuthController::class, 'login'])->name('login.submit')->middleware('throttle:3,1');
    });

    Route::prefix('register')->group(function () {
        Route::get('/', [AuthController::class, 'registerForm'])->name('register'); // Default route name
        Route::post('/', [AuthController::class, 'register'])->name('register.submit');
    });

    Route::prefix('forgot-password')->group(function () {
        Route::get('/', [AuthController::class, 'forgetPasswordForm'])->name('forgot-password');
        Route::post('/', [AuthController::class, 'forgotPassword'])->name('forgot-password.submit');
    });

    Route::prefix('password-reset')->group(function () {
        Route::get('{email}/{token}', [AuthController::class, 'resetPasswordForm'])->name('password.reset'); // Default route name
        Route::post('{email}/{token}', [AuthController::class, 'resetPassword'])->name('password.reset.submit');
    });
});

Route::middleware('auth')->group(function () {
    Route::prefix('verification')->group(function () {
        Route::get('notice', [AuthController::class, 'emailVerificationNotice'])->name('verification.notice');
        Route::post('resend', [AuthController::class, 'emailVerificationResend'])->name('verification.resend');
        Route::get('verification/{id}/{hash}', [AuthController::class, 'emailVerification'])->middleware('signed')->name('verification.verify'); // Default route name
    });

    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

Route::prefix('auth-user')->name('auth-user.')->middleware('auth')->group(function () {
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('index', [AuthController::class, 'dashboard'])->name('index');
    });

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('index', [AuthController::class, 'profileIndex'])->name('index');
        Route::patch('update', [AuthController::class, 'profileUpdate'])->name('update');
    });

    Route::prefix('account')->name('account.')->group(function () {
        Route::get('index', [AuthController::class, 'accountIndex'])->name('index');
        Route::patch('update', [AuthController::class, 'accountUpdate'])->name('update');
    });
});

Route::prefix('search')->name('search.')->group(function () {

    Route::middleware(['response.cache:3600,public,300,etag'])->group(function () {
        Route::get('per-pages', [SearchController::class, 'perPages'])->name('per-pages');
        Route::get('genders', [SearchController::class, 'genders'])->name('genders');
        Route::get('religions', [SearchController::class, 'religions'])->name('religions');
        Route::get('marital-statuses', [SearchController::class, 'maritalStatuses'])->name('marital-statuses');

        Route::get('activity-log-events', [SearchController::class, 'activityLogEvents'])->name('activity-log-events');
        Route::get('activity-log-subject-types', [SearchController::class, 'activityLogSubjectTypes'])->name('activity-log-subject-types');

        Route::get('page-sections', [SearchController::class, 'pageSections'])->name('page-sections');
        Route::get('menu-item-models', [SearchController::class, 'menuItemModels'])->name('menu-item-models');

        Route::get('event-positions', [SearchController::class, 'eventPositions'])->name('event-positions');

        Route::get('news-types', [SearchController::class, 'newsTypes'])->name('news-types');
        Route::get('user-permissions', [SearchController::class, 'userPermissions'])->name('user-permissions');
        Route::get('user-permissions-by-group', [SearchController::class, 'userPermissionsByGroup'])->name('user-permissions-by-group');
        Route::get('menu-types', [SearchController::class, 'menuTypes'])->name('menu-types');

        Route::get('google-adsence-types', [SearchController::class, 'googleAdsenceTypes'])->name('google-adsence-types');
        Route::get('google-adsence-positions', [SearchController::class, 'googleAdsencePositions'])->name('google-adsence-positions');
    });

    Route::middleware(['response.cache:60,public,30,etag'])->group(function () {
        Route::get('users', [SearchController::class, 'users'])->name('users');
        Route::get('languages', [SearchController::class, 'languages'])->name('languages');
        Route::get('categories', [SearchController::class, 'categories'])->name('categories');
        Route::get('menu-items', [SearchController::class, 'menuItems'])->name('menu-items');
        Route::get('tags', [SearchController::class, 'tags'])->name('tags');
        Route::get('locations', [SearchController::class, 'locations'])->name('locations');
        Route::get('events', [SearchController::class, 'events'])->name('events');
        Route::get('contributors', [SearchController::class, 'contributors'])->name('contributors');
        Route::get('pages', [SearchController::class, 'pages'])->name('pages');

        Route::get('medias', [SearchController::class, 'medias'])->name('medias');

        Route::get('category-tree', [SearchController::class, 'categoryTree'])->name('category-tree');
        Route::get('location-tree', [SearchController::class, 'locationTree'])->name('location-tree');
        Route::get('menu-item-tree', [SearchController::class, 'menuItemTree'])->name('menu-item-tree');
        Route::get('page-tree', [SearchController::class, 'pageTree'])->name('page-tree');

        Route::get('news', [SearchController::class, 'news'])->name('news');
        Route::get('breaking-news', [SearchController::class, 'breakingNews'])->name('breaking-news');
        Route::get('surveys', [SearchController::class, 'surveys'])->name('surveys');

        Route::get('user-permission/{slugOrId}', [SearchController::class, 'userPermission'])->name('user-permission');
        Route::get('news-type/{slugOrId}', [SearchController::class, 'newsType'])->name('news-type');
        Route::get('language/{slugOrId}', [SearchController::class, 'language'])->name('language');
        Route::get('category/{slugOrId}', [SearchController::class, 'category'])->name('category');
        Route::get('tag/{slugOrId}', [SearchController::class, 'tag'])->name('tag');
        Route::get('location/{slugOrId}', [SearchController::class, 'location'])->name('location');
        Route::get('event/{slugOrId}', [SearchController::class, 'event'])->name('event');
        Route::get('contributor/{slugOrId}', [SearchController::class, 'contributor'])->name('contributor');
        Route::get('menu-item/{slugOrId}', [SearchController::class, 'menuItem'])->name('menu-item');
        Route::get('survey/{slugOrId}', [SearchController::class, 'survey'])->name('survey');
    });

    Route::middleware(['response.cache:60,private,300,etag'])->get('user/{slugOrId}', [SearchController::class, 'user'])->name('user');
});

Route::prefix('back-office')->name('back-office.')->middleware(['auth', 'verified'])->group(function () {

    Route::prefix('medias')->name('medias.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::get('details/{slug}', [MediaController::class, 'details'])->name('details');
        Route::delete('delete/{slug}', [MediaController::class, 'delete'])->name('delete');

        Route::post('quick-save', [MediaController::class, 'quickSave'])->name('quick-save');
        Route::patch('quick-update/{slug}', [MediaController::class, 'quickUpdate'])->name('quick-update');

    });

    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('create', [UserController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [UserController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [UserController::class, 'details'])->name('details');

        Route::post('save', [UserController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [UserController::class, 'update'])->name('update');
        Route::delete('delete/{slug}', [UserController::class, 'delete'])->name('delete');
        Route::patch('active/{slug}', [UserController::class, 'active'])->name('active');
        Route::patch('inactive/{slug}', [UserController::class, 'inactive'])->name('inactive');
    });

    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/', [CategoryController::class, 'index'])->name('index');
        Route::get('create', [CategoryController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [CategoryController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [CategoryController::class, 'details'])->name('details');

        Route::post('save', [CategoryController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [CategoryController::class, 'update'])->name('update');
        Route::delete('delete/{slug}', [CategoryController::class, 'delete'])->name('delete');
    });

    Route::prefix('tags')->name('tags.')->group(function () {
        Route::get('/', [TagController::class, 'index'])->name('index');
        Route::get('create', [TagController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [TagController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [TagController::class, 'details'])->name('details');

        Route::post('save', [TagController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [TagController::class, 'update'])->name('update');
        Route::delete('delete/{slug}', [TagController::class, 'delete'])->name('delete');
    });

    Route::prefix('trends')->name('trends.')->group(function () {
        Route::get('/', [TrendController::class, 'index'])->name('index');
        Route::get('create', [TrendController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [TrendController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [TrendController::class, 'details'])->name('details');

        Route::post('save', [TrendController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [TrendController::class, 'update'])->name('update');
        Route::delete('delete/{slug}', [TrendController::class, 'delete'])->name('delete');
    });

    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/', [LocationController::class, 'index'])->name('index');
        Route::get('create', [LocationController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [LocationController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [LocationController::class, 'details'])->name('details');

        Route::post('save', [LocationController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [LocationController::class, 'update'])->name('update');
        Route::delete('delete/{slug}', [LocationController::class, 'delete'])->name('delete');
    });

    Route::prefix('events')->name('events.')->group(function () {
        Route::get('/', [EventController::class, 'index'])->name('index');
        Route::get('create', [EventController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [EventController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [EventController::class, 'details'])->name('details');

        Route::post('save', [EventController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [EventController::class, 'update'])->name('update');
        Route::delete('delete/{slug}', [EventController::class, 'delete'])->name('delete');
    });

    Route::prefix('contributors')->name('contributors.')->group(function () {
        Route::get('/', [ContributorController::class, 'index'])->name('index');
        Route::get('create', [ContributorController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [ContributorController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [ContributorController::class, 'details'])->name('details');

        Route::post('save', [ContributorController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [ContributorController::class, 'update'])->name('update');
        Route::delete('delete/{slug}', [ContributorController::class, 'delete'])->name('delete');
    });

    Route::prefix('news')->name('news.')->group(function () {
        Route::get('/', [NewsController::class, 'index'])->name('index');
        Route::get('create', [NewsController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [NewsController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [NewsController::class, 'details'])->name('details');

        Route::post('save', [NewsController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [NewsController::class, 'update'])->name('update');
        Route::patch('delete/{slug}', [NewsController::class, 'delete'])->name('delete');
        Route::patch('restore/{slug}', [NewsController::class, 'restore'])->name('restore');

        Route::prefix('{slug}/gallery-images')->name('gallery-images.')->group(function () {
            Route::post('save', [NewsController::class, 'galleryImageSave'])->name('save');
            Route::patch('update-sequence', [NewsController::class, 'galleryImageUpdateSequence'])->name('update-sequence');
            Route::patch('update/{mediaSlug}', [NewsController::class, 'galleryImageUpdate'])->name('update');
            Route::delete('delete/{mediaSlug}', [NewsController::class, 'galleryImageDelete'])->name('delete');
        });

        Route::prefix('{slug}/news-placements')->name('news-placements.')->group(function () {
            Route::get('/', [NewsController::class, 'newsPlacementByNewsIndex'])->name('index');
            Route::post('generate', [NewsController::class, 'newsPlacementGenerateForNews'])->name('generate');
            Route::patch('update', [NewsController::class, 'newsPlacementUpdateForNews'])->name('update');
            Route::get('details/{newsPlacementSlug}', [NewsController::class, 'newsPlacementDetails'])->name('details');
            Route::delete('delete/{newsPlacementSlug}', [NewsController::class, 'newsPlacementDelete'])->name('delete');
        });
    });

    Route::prefix('breaking-news')->name('breaking-news.')->group(function () {
        Route::get('/', [BreakingNewsController::class, 'index'])->name('index');
        Route::get('create', [BreakingNewsController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [BreakingNewsController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [BreakingNewsController::class, 'details'])->name('details');

        Route::post('save', [BreakingNewsController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [BreakingNewsController::class, 'update'])->name('update');
        Route::delete('delete/{slug}', [BreakingNewsController::class, 'delete'])->name('delete');
        Route::patch('trash/{slug}', [BreakingNewsController::class, 'trash'])->name('trash');
        Route::patch('restore/{slug}', [BreakingNewsController::class, 'restore'])->name('restore');
    });

    Route::prefix('pages')->name('pages.')->group(function () {
        Route::get('/', [BackOfficePageController::class, 'index'])->name('index');
        Route::get('create', [BackOfficePageController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [BackOfficePageController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [BackOfficePageController::class, 'details'])->name('details');

        Route::post('save', [BackOfficePageController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [BackOfficePageController::class, 'update'])->name('update');

        Route::patch('trash/{slug}', [BackOfficePageController::class, 'trash'])->name('trash');
        Route::patch('restore/{slug}', [BackOfficePageController::class, 'restore'])->name('restore');
        Route::delete('delete/{slug}', [BackOfficePageController::class, 'delete'])->name('delete');
    });

    Route::prefix('menus')->name('menus.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])->name('index');
        Route::get('create', [MenuController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [MenuController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [MenuController::class, 'details'])->name('details');

        Route::post('save', [MenuController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [MenuController::class, 'update'])->name('update');
        Route::patch('delete/{slug}', [MenuController::class, 'delete'])->name('delete');

        Route::prefix('{slug}/menu-items')->name('menu-items.')->group(function () {
            Route::get('/', [MenuController::class, 'menuItemIndex'])->name('index');
            Route::get('create', [MenuController::class, 'menuItemCreate'])->name('create');
            Route::get('edit/{menuItemSlug}', [MenuController::class, 'menuItemEdit'])->name('edit');
            Route::get('details/{menuItemSlug}', [MenuController::class, 'menuItemDetails'])->name('details');

            Route::post('save', [MenuController::class, 'menuItemUpdate'])->name('save');
            Route::patch('update/{menuItemSlug}', [MenuController::class, 'menuItemUpdate'])->name('update');
            Route::delete('delete/{menuItemSlug}', [MenuController::class, 'menuItemDelete'])->name('delete');
        });
    });

    Route::prefix('themes')->name('themes.')->group(function () {
        Route::get('/', [ThemeController::class, 'index'])->name('index');
        Route::get('edit/{slug}', [ThemeController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [ThemeController::class, 'details'])->name('details');

        Route::patch('update/{slug}', [ThemeController::class, 'update'])->name('update');
    });

    Route::prefix('google-adsences')->name('google-adsences.')->group(function () {
        Route::get('/', [GoogleAdsenceController::class, 'index'])->name('index');
        Route::get('create', [GoogleAdsenceController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [GoogleAdsenceController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [GoogleAdsenceController::class, 'details'])->name('details');

        Route::post('save', [GoogleAdsenceController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [GoogleAdsenceController::class, 'update'])->name('update');
        Route::delete('delete/{slug}', [GoogleAdsenceController::class, 'delete'])->name('delete');
    });

    Route::prefix('surveys')->name('surveys.')->group(function () {
        Route::get('/', [SurveyController::class, 'index'])->name('index');
        Route::get('create', [SurveyController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [SurveyController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [SurveyController::class, 'details'])->name('details');

        Route::post('save', [SurveyController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [SurveyController::class, 'update'])->name('update');
        Route::delete('delete/{slug}', [SurveyController::class, 'delete'])->name('delete');
        Route::patch('inactive/{slug}', [SurveyController::class, 'inactive'])->name('inactive');
        Route::patch('active/{slug}', [SurveyController::class, 'active'])->name('active');

        Route::prefix('{slug}/survey-questions')->name('survey-questions.')->group(function () {
            Route::get('/', [SurveyController::class, 'surveyQuestionIndex'])->name('index');
            Route::get('create', [SurveyController::class, 'surveyQuestionCreate'])->name('create');
            Route::get('edit/{surveyQuestionSlug}', [SurveyController::class, 'surveyQuestionEdit'])->name('edit');
            Route::get('details/{surveyQuestionSlug}', [SurveyController::class, 'surveyQuestionDetails'])->name('details');

            Route::post('save', [SurveyController::class, 'surveyQuestionSave'])->name('save');
            Route::patch('update/{surveyQuestionSlug}', [SurveyController::class, 'surveyQuestionUpdate'])->name('update');
            Route::delete('delete/{surveyQuestionSlug}', [SurveyController::class, 'surveyQuestiondDelete'])->name('delete');
        });
    });

    Route::prefix('activity-logs')->name('activity-logs.')->middleware(['is.super.admin'])->group(function () {
        Route::get('index', [ActivityLogController::class, 'index'])->name('index');

        Route::get('details/{slug}', [ActivityLogController::class, 'details'])->name('details');
        Route::get('{modelSlug}/show-all/{recordSlug}', [ActivityLogController::class, 'indexForModel'])->name('show-all');

        Route::delete('delete/{slug}', [ActivityLogController::class, 'delete'])->name('delete');
    });

    Route::prefix('queue-monitor')->name('queue-monitor.')->middleware(['is.super.admin'])->group(function () {
        Route::get('/', ShowQueueMonitorController::class)->name('index');
    });

    Route::prefix('settings')->name('settings.')->middleware(['is.super.admin'])->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');

        Route::prefix('queue')->name('queue.')->group(function () {
            Route::get('start', [SettingController::class, 'queueStart'])->name('start');
            Route::get('restarted', [SettingController::class, 'queueReStart'])->name('restart');
            Route::get('clear', [SettingController::class, 'queueClear'])->name('clear');
            Route::get('flush', [SettingController::class, 'queueFlush'])->name('flush');

            Route::prefix('monitor')->name('monitor.')->group(function () {
                Route::get('stale', [SettingController::class, 'queueMonitorStale'])->name('stale');
                Route::get('purge', [SettingController::class, 'queueMonitorPurge'])->name('purge');
            });
        });

        Route::prefix('schedule')->name('schedule.')->group(function () {
            Route::get('start', [SettingController::class, 'scheduleStart'])->name('start');
            Route::get('stop', [SettingController::class, 'scheduleStop'])->name('stop');
        });

        Route::prefix('robots-txt')->name('robots-txt.')->group(function () {
            Route::get('edit', [SettingController::class, 'robotsTxtEdit'])->name('edit');
            Route::post('save', [SettingController::class, 'robotsTxtSave'])->name('save');
        });

        Route::prefix('ads-txt')->name('ads-txt.')->group(function () {
            Route::get('edit', [SettingController::class, 'adsTxtEdit'])->name('edit');
            Route::post('save', [SettingController::class, 'adsTxtSave'])->name('save');
        });
    });
});

Route::get('/', function () {
    return redirect()->route('home');
});

Route::prefix('{languageCode}')->name('localized.')->where(['languageCode' => 'en|bn'])->group(function () {
    Route::prefix('sitemaps')->name('sitemaps.')->middleware(['xml.response'])->group(function () {
        Route::get('index.xml', [SitemapController::class, 'localizedIndex'])->name('index');

        Route::get('categories.xml', [SitemapController::class, 'localizedCategories'])->name('categories');
        Route::get('tags.xml', [SitemapController::class, 'localizedTags'])->name('tags');
        Route::get('locations.xml', [SitemapController::class, 'localizedLocations'])->name('locations');
        Route::get('events.xml', [SitemapController::class, 'localizedEvents'])->name('events');
        Route::get('contributors.xml', [SitemapController::class, 'localizedContributors'])->name('contributors');

        Route::get('news.xml', [SitemapController::class, 'localizedNews'])->name('news');
        Route::get('latest-news.xml', [SitemapController::class, 'localizedLatestNews'])->name('latest-news');

        Route::get('categories/{slugTree}/news.xml', [SitemapController::class, 'localizedCategoryNews'])->where('slugTree', '.*')->name('category.news');
        Route::get('locations/{slugTree}/news.xml', [SitemapController::class, 'localizedLocationNews'])->where('slugTree', '.*')->name('location.news');
        Route::get('events/{slug}/news.xml', [SitemapController::class, 'localizedEventNews'])->name('event.news');
        Route::get('tags/{slug}/news.xml', [SitemapController::class, 'localizedTagNews'])->name('tag.news');
        Route::get('contributors/{slug}/news.xml', [SitemapController::class, 'localizedContributorNews'])->name('contributor.news');

    });

    Route::prefix('feeds')->name('feeds.')->group(function () {
        Route::prefix('rss')->name('rss.')->middleware(['feed.response:rss'])->group(function () {
            Route::get('news.xml', [FeedController::class, 'localizedNews'])->name('news');
            Route::get('latest-news.xml', [FeedController::class, 'localizedLatestNews'])->name('latest-news');
            Route::get('categories/{slugTree}/news.xml', [FeedController::class, 'localizedCategoryNews'])->where('slugTree', '.*')->name('category.news');
            Route::get('locations/{slugTree}/news.xml', [FeedController::class, 'localizedLocationNews'])->where('slugTree', '.*')->name('location.news');
            Route::get('events/{slug}/news.xml', [FeedController::class, 'localizedEventNews'])->name('event.news');
            Route::get('tags/{slug}/news.xml', [FeedController::class, 'localizedTagNews'])->name('tag.news');
            Route::get('contributors/{slug}/news.xml', [FeedController::class, 'localizedContributorNews'])->name('contributor.news');
        });

        Route::prefix('atom')->name('atom.')->middleware(['feed.response:atom'])->group(function () {
            Route::get('news.xml', [FeedController::class, 'localizedNews'])->name('news');
            Route::get('latest-news.xml', [FeedController::class, 'localizedLatestNews'])->name('latest-news');
            Route::get('categories/{slugTree}/news.xml', [FeedController::class, 'localizedCategoryNews'])->where('slugTree', '.*')->name('category.news');
            Route::get('locations/{slugTree}/news.xml', [FeedController::class, 'localizedLocationNews'])->where('slugTree', '.*')->name('location.news');
            Route::get('events/{slug}/news.xml', [FeedController::class, 'localizedEventNews'])->name('event.news');
            Route::get('tags/{slug}/news.xml', [FeedController::class, 'localizedTagNews'])->name('tag.news');
            Route::get('contributors/{slug}/news.xml', [FeedController::class, 'localizedCcontributorNews'])->name('contributor.news');
        });
    });

    Route::prefix('site')->name('site.')->group(function () {
        Route::prefix('menus')->name('menus.')->group(function () {
            Route::get('header-menu-items', [SiteController::class, 'localizedMenuHeaderMenuMenuItems'])->name('header-menu-items');
            Route::get('off-canvas-menu-items', [SiteController::class, 'localizedMenuOffCanvasMenuMenuItems'])->name('off-canvas-menu-items');
            Route::get('topbar-menu-items', [SiteController::class, 'localizedMenuTopbarMenuMenuItems'])->name('topbar-menu-items');
            Route::get('footer-menu-items', [SiteController::class, 'localizedMenuFooterMenuMenuItems'])->name('footer-menu-items');
        });

        Route::prefix('menu-items/{slug}')->name('menu-items.')->group(function () {
            Route::get('sub-menu-items', [SiteController::class, 'menuItemSubMenuItems'])->name('sub-menu-items');
        });
    });

    Route::middleware(['response.cache:30,public,15,etag'])->group(function () {

        Route::get('home', [PageController::class, 'localizedHome'])->name('home');
        Route::prefix('home')->name('home.')->group(function () {
            Route::get('event/{slug}/news', [PageController::class, 'localizedHomeEventNews'])->name('event-news');
            Route::get('category/{slug}', [PageController::class, 'localizedHomeCategory'])->name('category');
            Route::get('category/{slug}/news', [PageController::class, 'localizedHomeCategoryNews'])->name('category-news');
            Route::get('news-type/{slug}/news', [PageController::class, 'localizedHomeNewsTypeNews'])->name('news-type-news');

            Route::prefix('surveys')->name('surveys.')->group(function () {
                Route::get('get', [PageController::class, 'localizedHomeSurveys'])->name('get');
                Route::post('{slug}/survey-questions/{surveyQuestionSlug}/submit', [PageController::class, 'localizedHomeSurveySurveyQuestionSubmit'])->name('survey-questions-submit');
            });

        });

        Route::get('latest', [PageController::class, 'localizedLatest'])->name('latest');
        Route::get('search', [PageController::class, 'localizedSearch'])->name('search');

        Route::get('videos', [PageController::class, 'localizedVideos'])->name('videos');
        Route::get('image-galleries', [PageController::class, 'localizedImageGalleries'])->name('image-galleries');

        Route::get('tags/{slug}', [PageController::class, 'localizedTagNews'])->name('tag.news');
        Route::get('contributors/{slug}', [PageController::class, 'localizedContributorNews'])->name('contributor.news');
        Route::get('events/{slug}', [PageController::class, 'localizedEventNews'])->name('event.news');

        Route::get('categories/{slugTree}', [PageController::class, 'localizedCategoryNews'])->where('slugTree', '.*')->name('category.news');
        Route::get('locations/{slugTree}', [PageController::class, 'localizedLocationNews'])->where('slugTree', '.*')->name('location.news');
        Route::get('category/{slugTree}/location-max-depth-and-level', [PageController::class, 'localizedCategoryLocationMaxDepthAndLevel'])->where('slugTree', '.*')->name('category.location-max-depth-and-level');

        Route::get('news/{slug}', [PageController::class, 'localizedNewsDetails'])->name('news.details');

        Route::get('{slugTree}', [PageController::class, 'localizedPage'])->where('slugTree', '.*')->name('page');
    });
});

Route::prefix('sitemaps')->name('sitemaps.')->middleware(['xml.response'])->group(function () {
    Route::get('index.xml', [SitemapController::class, 'index'])->name('index');

    Route::get('categories.xml', [SitemapController::class, 'categories'])->name('categories');
    Route::get('tags.xml', [SitemapController::class, 'tags'])->name('tags');
    Route::get('locations.xml', [SitemapController::class, 'locations'])->name('locations');
    Route::get('events.xml', [SitemapController::class, 'events'])->name('events');
    Route::get('contributors.xml', [SitemapController::class, 'contributors'])->name('contributors');

    Route::get('news.xml', [SitemapController::class, 'news'])->name('news');
    Route::get('latest-news.xml', [SitemapController::class, 'latestNews'])->name('latest-news');

    Route::get('categories/{slugTree}/news.xml', [SitemapController::class, 'categoryNews'])->where('slugTree', '.*')->name('category.news');
    Route::get('locations/{slugTree}/news.xml', [SitemapController::class, 'locationNews'])->where('slugTree', '.*')->name('location.news');
    Route::get('events/{slug}/news.xml', [SitemapController::class, 'eventNews'])->name('event.news');
    Route::get('tags/{slug}/news.xml', [SitemapController::class, 'tagNews'])->name('tag.news');
    Route::get('contributors/{slug}/news.xml', [SitemapController::class, 'contributorNews'])->name('contributor.news');

});

Route::prefix('feeds')->name('feeds.')->group(function () {
    Route::prefix('rss')->name('rss.')->middleware(['feed.response:rss'])->group(function () {
        Route::get('news.xml', [FeedController::class, 'news'])->name('news');
        Route::get('latest-news.xml', [FeedController::class, 'latestNews'])->name('latest-news');
        Route::get('categories/{slugTree}/news.xml', [FeedController::class, 'categoryNews'])->where('slugTree', '.*')->name('category.news');
        Route::get('locations/{slugTree}/news.xml', [FeedController::class, 'locationNews'])->where('slugTree', '.*')->name('location.news');
        Route::get('events/{slug}/news.xml', [FeedController::class, 'eventNews'])->name('event.news');
        Route::get('tags/{slug}/news.xml', [FeedController::class, 'tagNews'])->name('tag.news');
        Route::get('contributors/{slug}/news.xml', [FeedController::class, 'contributorNews'])->name('contributor.news');
    });

    Route::prefix('atom')->name('atom.')->middleware(['feed.response:atom'])->group(function () {
        Route::get('news.xml', [FeedController::class, 'news'])->name('news');
        Route::get('latest-news.xml', [FeedController::class, 'latestNews'])->name('latest-news');
        Route::get('categories/{slugTree}/news.xml', [FeedController::class, 'categoryNews'])->where('slugTree', '.*')->name('category.news');
        Route::get('locations/{slugTree}/news.xml', [FeedController::class, 'locationNews'])->where('slugTree', '.*')->name('location.news');
        Route::get('events/{slug}/news.xml', [FeedController::class, 'eventNews'])->name('event.news');
        Route::get('tags/{slug}/news.xml', [FeedController::class, 'tagNews'])->name('tag.news');
        Route::get('contributors/{slug}/news.xml', [FeedController::class, 'contributorNews'])->name('contributor.news');
    });
});

Route::prefix('site')->name('site.')->group(function () {
    Route::get('languages/{code}', [SiteController::class, 'language'])->name('language');
    Route::get('defalult-language', [SiteController::class, 'defaultLanguage'])->name('default-language');
    Route::post('language-change/{slugOrId}', [SiteController::class, 'languageChange'])->name('language-change');

    Route::get('themes', [SiteController::class, 'themes'])->name('themes');
    Route::get('breaking-news', [SiteController::class, 'breakingNews'])->name('breaking-news');
    Route::get('languages', [SiteController::class, 'languages'])->name('languages');

    Route::get('google-adsences', [SiteController::class, 'getGoogleAdsence'])->name('google-adsences');

    Route::prefix('menus')->name('menus.')->group(function () {
        Route::get('header-menu-items', [SiteController::class, 'menuHeaderMenuMenuItems'])->name('header-menu-items');
        Route::get('off-canvas-menu-items', [SiteController::class, 'menuOffCanvasMenuMenuItems'])->name('off-canvas-menu-items');
        Route::get('topbar-menu-items', [SiteController::class, 'menuTopbarMenuMenuItems'])->name('topbar-menu-items');
        Route::get('footer-menu-items', [SiteController::class, 'menuFooterMenuMenuItems'])->name('footer-menu-items');
    });

    Route::prefix('menu-items/{slug}')->name('menu-items.')->group(function () {
        Route::get('sub-menu-items', [SiteController::class, 'menuItemSubMenuItems'])->name('sub-menu-items');
    });
});

Route::middleware(['response.cache:30,public,15,etag'])->group(function () {

    Route::get('home', [PageController::class, 'home'])->name('home');
    Route::prefix('home')->name('home.')->group(function () {
        Route::get('event/{slug}/news', [PageController::class, 'homeEventNews'])->name('event-news');
        Route::get('category/{slug}', [PageController::class, 'homeCategory'])->name('category');
        Route::get('category/{slug}/news', [PageController::class, 'homeCategoryNews'])->name('category-news');
        Route::get('news-type/{slug}/news', [PageController::class, 'homeNewsTypeNews'])->name('news-type-news');

        Route::prefix('surveys')->name('surveys.')->group(function () {
            Route::get('get', [PageController::class, 'homeSurveys'])->name('get');
            Route::post('{slug}/survey-questions/{surveyQuestionSlug}/submit', [PageController::class, 'homeSurveySurveyQuestionSubmit'])->name('survey-questions-submit');
        });

    });

    Route::get('latest', [PageController::class, 'latest'])->name('latest');
    Route::get('search', [PageController::class, 'search'])->name('search');

    Route::get('videos', [PageController::class, 'videos'])->name('videos');
    Route::get('image-galleries', [PageController::class, 'imageGalleries'])->name('image-galleries');

    Route::get('tags/{slug}', [PageController::class, 'tagNews'])->name('tag.news');
    Route::get('contributors/{slug}', [PageController::class, 'contributorNews'])->name('contributor.news');
    Route::get('events/{slug}', [PageController::class, 'eventNews'])->name('event.news');

    Route::get('categories/{slugTree}', [PageController::class, 'categoryNews'])->where('slugTree', '.*')->name('category.news');
    Route::get('locations/{slugTree}', [PageController::class, 'locationNews'])->where('slugTree', '.*')->name('location.news');
    Route::get('category/{slugTree}/location-max-depth-and-level', [PageController::class, 'categoryLocationMaxDepthAndLevel'])->where('slugTree', '.*')->name('category.location-max-depth-and-level');

    Route::get('news/{slug}', [PageController::class, 'newsDetails'])->name('news.details');

    Route::get('{slugTree}', [PageController::class, 'page'])->where('slugTree', '.*')->name('page');

});
