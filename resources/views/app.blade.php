@php
    use App\Helpers\ThemeHelper;

    $isPublicPage = !request()->is('back-office/*') && !request()->is('auth-user/*');

    $googleAdsenseClientId = data_get(
        $themeGoogleAd?->options,
        ThemeHelper::OPTION_GOOGLE_AD_ADSENSE_CLIENT_ID . '.value',
        null
    );

    $googleAdEnable = data_get(
        $themeGoogleAd?->options,
        ThemeHelper::OPTION_GOOGLE_AD_ENABLE . '.value',
        false
    );

    $googleSearchConsoleHeader = data_get(
        $themeGoogleService?->options,
        ThemeHelper::OPTION_GOOGLE_SEARCH_CONSOLE_HEADER . '.value',
        null
    );

    $googleAnalyticHeader = data_get(
        $themeGoogleService?->options,
        ThemeHelper::OPTION_GOOGLE_ANALYTIC_HEADER . '.value',
        null
    );

    $googleTagManagerHeader = data_get(
        $themeGoogleService?->options,
        ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_HEADER . '.value',
        null
    );

    $googleTagManagerBody = data_get(
        $themeGoogleService?->options,
        ThemeHelper::OPTION_GOOGLE_TAG_MANAGER_BODY . '.value',
        null
    );

    $renderRawHtml = function ($value) {
        return html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    };

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
            @if ($googleSearchConsoleHeader)
                {!! $renderRawHtml($googleSearchConsoleHeader) !!}
            @endif

            @if ($googleAnalyticHeader)
                {!! $renderRawHtml($googleAnalyticHeader) !!}
            @endif

            @if ($googleTagManagerHeader)
                {!! $renderRawHtml($googleTagManagerHeader) !!}
            @endif

            @if ($googleAdEnable && $googleAdsenseClientId)
                <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={{ $googleAdsenseClientId }}"
                    crossorigin="anonymous"></script>
            @endif
        @endif

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @routes

        @inertiaHead
    </head>

    <body>
        @if ($isPublicPage)
            @if ($googleTagManagerBody)
                {!! $renderRawHtml($googleTagManagerBody) !!}
            @endif
        @endif

        @inertia
    </body>

</html>
