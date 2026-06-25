<?php

use App\Http\Middleware\FeedResponse;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\IsSuperAdmin;
use App\Http\Middleware\ResponseCache;
use App\Http\Middleware\XmlResponse;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);
        $middleware->alias([
            'handle.inertia.middleware' => HandleInertiaRequests::class,
            'is.super.admin'            => IsSuperAdmin::class,
            'response.cache'            => ResponseCache::class,
            'xml.response'              => XmlResponse::class,
            'feed.response'             => FeedResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
