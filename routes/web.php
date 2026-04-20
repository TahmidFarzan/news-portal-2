<?php

use App\Http\Controllers\AuthController;
//
use App\Http\Controllers\BackOffice\ActivityLogController;
use App\Http\Controllers\BackOffice\UserController;

// Backoffice
use App\Http\Controllers\PageController;
use App\Http\Controllers\SearchController;
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

Route::prefix('search')->name('search.')->group(function () {
    // System
    Route::get('per-pages', [SearchController::class, 'perPages'])->name('per-pages');
    Route::get('genders', [SearchController::class, 'genders'])->name('genders');
    Route::get('religions', [SearchController::class, 'religions'])->name('religions');
    Route::get('marital-statuses', [SearchController::class, 'maritalStatuses'])->name('marital-statuses');

    Route::get('activity-log-events', [SearchController::class, 'activityLogEvents'])->name('activity-log-events');
    Route::get('activity-log-subject-types', [SearchController::class, 'activityLogSubjectTypes'])->name('activity-log-subject-types');

    Route::get('record-statuses', [SearchController::class, 'recordStatuses'])->name('record-statuses');
    Route::get('commission-types', [SearchController::class, 'commissionTypes'])->name('commission-types');

    // Model || DB
    Route::get('users', [SearchController::class, 'users'])->name('users');

    Route::prefix('users')->name('users.')->group(function () {
        Route::prefix('supervisors')->name('supervisors.')->group(function () {
            Route::prefix('nested-tree')->name('nested-tree.')->group(function () {
                Route::get('/', [SearchController::class, 'userSupervisorsNestedTreeFull'])->name('full');
                Route::get('{slugOrId}', [SearchController::class, 'userSupervisorsNestedTreeUserByUser'])->name('by-user');
            });
        });
    });

    Route::get('user-roles', [SearchController::class, 'userRoles'])->name('user-roles');

    Route::get('user/{slugOrId}', [SearchController::class, 'user'])->name('user');
    Route::get('user-role/{slugOrId}', [SearchController::class, 'userRole'])->name('user-role');
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

Route::prefix('back-office')->name('back-office.')->group(function () {

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

    Route::prefix('activity-logs')->name('activity-logs.')->group(function () {
        Route::get('index', [ActivityLogController::class, 'index'])->name('index');

        Route::get('details/{slug}', [ActivityLogController::class, 'details'])->name('details');
        Route::get('{modelSlug}/show-all/{recordSlug}', [ActivityLogController::class, 'indexForModel'])->name('show-all');

        Route::delete('delete/{slug}', [ActivityLogController::class, 'delete'])->name('delete');
    });

});

Route::get('/', function () {
    return redirect()->route('home');
});
Route::get('home', [PageController::class, 'home'])->name('home');
