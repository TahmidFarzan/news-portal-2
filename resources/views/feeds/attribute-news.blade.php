{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}

@php
    if (method_exists($newsItems, 'appends')) {
        $newsItems->appends(request()->except('page'));
    }
@endphp

@switch(strtoupper($viewsType ?? 'RSS'))

    @case('ATOM')
        <feed xmlns="http://www.w3.org/2005/Atom">
            <title>{{ config('app.name') }} - All News</title>
            <subtitle>All published news from {{ config('app.name') }} form {{ $attribute->name }}</subtitle>

            <link href="{{ $feedLink }}" />
            <link href="{{ $selfUrl }}" rel="self" />

            @if(method_exists($newsItems, 'url'))
                <link href="{{ $newsItems->url(1) }}" rel="first" />

                @if($newsItems->previousPageUrl())
                    <link href="{{ $newsItems->previousPageUrl() }}" rel="previous" />
                @endif

                @if($newsItems->nextPageUrl())
                    <link href="{{ $newsItems->nextPageUrl() }}" rel="next" />
                @endif

                <link href="{{ $newsItems->url($newsItems->lastPage()) }}" rel="last" />
            @endif

            <id>{{ $selfUrl }}</id>
            <updated>{{ now()->toAtomString() }}</updated>

            @foreach($newsItems as $newsItem)
                <x-feeds.news-component :news="$newsItem" :views-type="$viewsType" />
            @endforeach
        </feed>
        @break

    @case('RSS')
    @default
        <rss version="2.0">
            <channel>
                <title>{{ config('app.name') }} - All News</title>
                <link>{{ $feedLink }}</link>
                <description>All published news from {{ config('app.name') }} form {{ $attribute->name }}</description>
                <lastBuildDate>{{ now()->toRfc2822String() }}</lastBuildDate>

                @if(method_exists($newsItems, 'currentPage'))
                    <currentPage>{{ $newsItems->currentPage() }}</currentPage>
                    <lastPage>{{ $newsItems->lastPage() }}</lastPage>
                    <total>{{ $newsItems->total() }}</total>
                    <perPage>{{ $newsItems->perPage() }}</perPage>

                    @if($newsItems->previousPageUrl())
                        <previousPageUrl>{{ $newsItems->previousPageUrl() }}</previousPageUrl>
                    @endif

                    @if($newsItems->nextPageUrl())
                        <nextPageUrl>{{ $newsItems->nextPageUrl() }}</nextPageUrl>
                    @endif
                @endif

                @foreach($newsItems as $news)
                    <x-feeds.news-component :news="$news" :views-type="$viewsType" />
                @endforeach
            </channel>
        </rss>
        @break

@endswitch
