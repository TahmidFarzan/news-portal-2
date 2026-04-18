<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="copyright" content="Copyright &copy; {{ now()->format('Y') }}. All Rights &reg; Reserved by {{ config("app.url") }}">

        @if (request()->is('/'))
            <meta http-equiv="refresh" content="300">
        @endif

        @if (!request()->is('back-office/*') && !request()->is('auth-user/*'))
            <meta name="robots" content="index, follow">
            <link rel="canonical" href="{{ request()->fullUrl() }}">
        @endif

        <title inertia>{{ config('app.name', 'News Portal 2') }}</title>

        <link href="{{ config('app.app_favicon') }}" rel="icon" loading="lazy">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @routes

        @inertiaHead
    </head>

    <body>
        @inertia
    </body>
</html>
