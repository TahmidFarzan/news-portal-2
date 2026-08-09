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
            $themeHeader = app(SiteService::class)->themeHeader();
            $themeBody = app(SiteService::class)->themeBody();
            $themeGoogleAdsenseClientId = app(SiteService::class)->themeGoogleAdsenseClientId();

            $view->with(compact('themeHeader','themeBody','themeGoogleAdsenseClientId'));
        });
    }
}
