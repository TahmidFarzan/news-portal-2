{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}

@switch(strtoupper($viewsType ?? 'RSS'))

    @case('ATOM')
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>{{ config('app.name') }} - Latest News</title>
            <subtitle>Latest news from {{ config('app.name') }}</subtitle>

            <link href="{{ $feedLink }}" />
            <link href="{{ $selfUrl }}" rel="self" />

            @if(method_exists($newses, 'url'))
                <link href="{{ $newses->url(1) }}" rel="first" />

                @if($newses->previousPageUrl())
                    <link href="{{ $newses->previousPageUrl() }}" rel="previous" />
                @endif

                @if($newses->nextPageUrl())
                    <link href="{{ $newses->nextPageUrl() }}" rel="next" />
                @endif

                <link href="{{ $newses->url($newses->lastPage()) }}" rel="last" />
            @endif

            <id>{{ $selfUrl }}</id>
            <updated>{{ now()->toAtomString() }}</updated>

            @foreach($newses as $news)
                <x-feeds.news-component :news="$news" :views-type="$viewsType" />
            @endforeach
        </feed>
        @break

    @case('RSS')
    @default
        <rss version="2.0">
            <channel>
                <title>{{ config('app.name') }} - Latest News</title>
                <link>{{ $feedLink }}</link>
                <description>Latest news from {{ config('app.name') }}</description>
                <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>

                @foreach($newses as $news)
                    <x-feeds.news-component :news="$news" :views-type="$viewsType" />
                @endforeach
            </channel>
        </rss>
        @break

@endswitch
