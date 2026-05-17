@switch($viewsType)
    @case('ATOM')
        <entry @if (!empty($news->language)) xml:lang="{{ $news->language?->code }}" @endif>
            <title>{{ $news->title ?? 'Untitled News' }}</title>
            <link href="{{ $news->public_url }}" />
            <id>{{ $news->public_url }}</id>

            @if (!empty($news->brief))
                <summary>{{ $news->brief }}</summary>
            @endif

            @if (!empty($news->body))
                <content type="text">{{ strip_tags($news->body ?? '') }}</content>
            @endif

            <published>{{ ($news->created_at ?? now())->toAtomString() }}</published>
            <updated>{{ ($news->updated_at ?? now())->toAtomString() }}</updated>
        </entry>
    @break

    @case('RSS')

        @default
            <item>
                <title>{{ $news->title ?? 'Untitled News' }}</title>
                <link>{{ $news->public_url }}</link>
                <guid isPermaLink="false">{{ $news->id ?? $news->public_url }}</guid>

                @if (!empty($news->brief))
                    <description>{{ $news->brief }}</description>
                @elseif (!empty($news->body))
                    <description>{{ strip_tags($news->body ?? '') }}</description>
                @endif

                <pubDate>{{ ($news->created_at ?? now())->toRfc2822String() }}</pubDate>

                @if (!empty($news->language))
                    <language>{{ $news->language?->code }}</language>
                @endif
            </item>
        @break

    @endswitch
