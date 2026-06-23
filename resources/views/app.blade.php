@php
    $isPublicPage = !request()->is('back-office/*') && !request()->is('auth-user/*');
    $googleAdsClientId = config('util.test_client_id');

    $renderRawHtml = function ($value) {
        return html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    };

    if ($themeGoogleAdCLientId && $themeGoogleAdCLientId->value) {
        $googleAdsClientId = $themeGoogleAdCLientId->value;
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <meta name="copyright"
            content="Copyright &copy; {{ now()->format('Y') }}. All Rights &reg; Reserved by {{ config('app.url') }}">

        @if (request()->is('/'))
            <meta http-equiv="refresh" content="300">
        @endif

        @if ($isPublicPage)
            <meta name="robots" content="index, follow">
            <link rel="canonical" href="{{ request()->fullUrl() }}">
        @endif

        <title inertia>{{ config('app.name', 'News Portal') }}</title>

        <link href="{{ config('app.app_favicon') }}" rel="icon">

        @if ($isPublicPage)
            @foreach ($themeHeader ?? [] as $perHeaderMeta)
                {!! $renderRawHtml($perHeaderMeta->value) !!}
            @endforeach

            <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $googleAdsClientId }}"
                crossorigin="anonymous"></script>
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @routes

        @inertiaHead
    </head>

    <body>
        @if ($isPublicPage)
            @foreach ($themeBody ?? [] as $perBodyMeta)
                {!! $renderRawHtml($perBodyMeta->value) !!}
            @endforeach
        @endif

        @inertia
    </body>

</html>
