<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\ResponseCache;
use App\Http\Middleware\UserRoleCheck;
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
            'xml.response'              => XmlResponse::class,
            'user.role.check'           => UserRoleCheck::class,
            'response.cache'            => ResponseCache::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
