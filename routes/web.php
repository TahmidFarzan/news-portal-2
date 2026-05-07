<?php

use App\Http\Controllers\AuthController;
//
use App\Http\Controllers\BackOffice\ActivityLogController;
use App\Http\Controllers\BackOffice\CategoryController;
use App\Http\Controllers\BackOffice\ContributorController;
use App\Http\Controllers\BackOffice\EventController;
use App\Http\Controllers\BackOffice\LanguageController;
use App\Http\Controllers\BackOffice\LocationController;
use App\Http\Controllers\BackOffice\NewsController;

// Backoffice
use App\Http\Controllers\BackOffice\MediaController;
use App\Http\Controllers\BackOffice\TagController;
use App\Http\Controllers\BackOffice\TrendController;
use App\Http\Controllers\BackOffice\UserController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

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

Route::middleware('auth')->prefix('auth-user')->name('auth-user.')->group(function () {
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
    // System
    Route::get('per-pages', [SearchController::class, 'perPages'])->name('per-pages');
    Route::get('genders', [SearchController::class, 'genders'])->name('genders');
    Route::get('religions', [SearchController::class, 'religions'])->name('religions');
    Route::get('marital-statuses', [SearchController::class, 'maritalStatuses'])->name('marital-statuses');

    Route::get('activity-log-events', [SearchController::class, 'activityLogEvents'])->name('activity-log-events');
    Route::get('activity-log-subject-types', [SearchController::class, 'activityLogSubjectTypes'])->name('activity-log-subject-types');

    Route::get('page-sections', [SearchController::class, 'pageSections'])->name('page-sections');


    // Model || DB
    Route::get('users', [SearchController::class, 'users'])->name('users');
    Route::get('user-roles', [SearchController::class, 'userRoles'])->name('user-roles');
    Route::get('languages', [SearchController::class, 'languages'])->name('languages');
    Route::get('categories', [SearchController::class, 'categories'])->name('categories');
    Route::get('tags', [SearchController::class, 'tags'])->name('tags');
    Route::get('locations', [SearchController::class, 'locations'])->name('locations');
    Route::get('events', [SearchController::class, 'events'])->name('events');
    Route::get('contributors', [SearchController::class, 'contributors'])->name('contributors');

    Route::get('medias', [SearchController::class, 'medias'])->name('medias');

    Route::get('category-tree', [SearchController::class, 'categoryTree'])->name('category-tree');
    Route::get('location-tree', [SearchController::class, 'locationTree'])->name('location-tree');

    Route::get('newses', [SearchController::class, 'newses'])->name('newses');

    Route::get('user/{slugOrId}', [SearchController::class, 'user'])->name('user');
    Route::get('user-role/{slugOrId}', [SearchController::class, 'userRole'])->name('user-role');
    Route::get('language/{slugOrId}', [SearchController::class, 'language'])->name('language');
    Route::get('category/{slugOrId}', [SearchController::class, 'category'])->name('category');
    Route::get('tag/{slugOrId}', [SearchController::class, 'tag'])->name('tag');
    Route::get('location/{slugOrId}', [SearchController::class, 'location'])->name('location');
    Route::get('event/{slugOrId}', [SearchController::class, 'event'])->name('event');
    Route::get('contributor/{slugOrId}', [SearchController::class, 'contributor'])->name('contributor');

});

Route::prefix('back-office')->name('back-office.')->group(function () {

    Route::prefix('medias')->name('medias.')->group(function () {
        Route::get('/', [MediaController::class, 'index'])->name('index');
        Route::get('details/{slug}', [MediaController::class, 'details'])->name('details');
        Route::delete('delete/{slug}', [MediaController::class, 'delete'])->name('delete');

        Route::post('quick-save', [MediaController::class, 'quickSave'])->name('quick-save');

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

    Route::prefix('languages')->name('languages.')->group(function () {
        Route::get('/', [LanguageController::class, 'index'])->name('index');
        Route::get('create', [LanguageController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [LanguageController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [LanguageController::class, 'details'])->name('details');

        Route::post('save', [LanguageController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [LanguageController::class, 'update'])->name('update');
        Route::delete('delete/{slug}', [LanguageController::class, 'delete'])->name('delete');
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

    Route::prefix('newses')->name('newses.')->group(function () {
        Route::get('/', [NewsController::class, 'index'])->name('index');
        Route::get('create', [NewsController::class, 'create'])->name('create');
        Route::get('edit/{slug}', [NewsController::class, 'edit'])->name('edit');
        Route::get('details/{slug}', [NewsController::class, 'details'])->name('details');

        Route::post('save', [NewsController::class, 'save'])->name('save');
        Route::patch('update/{slug}', [NewsController::class, 'update'])->name('update');
        Route::patch('delete/{slug}', [NewsController::class, 'delete'])->name('delete');
        Route::patch('restore/{slug}', [NewsController::class, 'restore'])->name('restore');
    });

    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('index', [ActivityLogController::class, 'index'])->name('index');

        Route::get('details/{slug}', [ActivityLogController::class, 'details'])->name('details');
        Route::get('{modelSlug}/show-all/{recordSlug}', [ActivityLogController::class, 'indexForModel'])->name('show-all');

        Route::delete('delete/{slug}', [ActivityLogController::class, 'delete'])->name('delete');
    });

});

Route::prefix('sitemaps')->name('sitemaps.')->group(function () {
    Route::get('index.xml', [SitemapController::class, 'index'])->name('index');

    Route::get('categories.xml', [SitemapController::class, 'categories'])->name('categories');
    Route::get('tags.xml', [SitemapController::class, 'tags'])->name('tags');
    Route::get('locations.xml', [SitemapController::class, 'locations'])->name('locations');
    Route::get('events.xml', [SitemapController::class, 'events'])->name('events');
    Route::get('contributors.xml', [SitemapController::class, 'contributors'])->name('contributors');

    Route::get('newses.xml', [SitemapController::class, 'newses'])->name('newses');
    Route::get('latest-newses.xml', [SitemapController::class, 'latestNewses'])->name('latest-newses');
});

Route::get('/', function () {
    return redirect()->route('home');
});
Route::get('home', [PageController::class, 'home'])->name('home');
