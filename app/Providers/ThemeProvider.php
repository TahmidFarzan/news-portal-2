<?php
namespace App\Providers;

use App\Services\SiteService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ThemeProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer('app', function ($view) {
            $themeGoogleService = app(SiteService::class)->themeGoogleService();
            $themeGoogleAd = app(SiteService::class)->themeGoogleAd();

            $view->with(compact('themeGoogleService','themeGoogleAd'));
        });
    }
}
