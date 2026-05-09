@php
    $keywords = $story->seo_keywords;

    foreach ($story->tags()->get() as $tag) {
        $keywords = $keywords . ',' . $tag->name;
    }
@endphp
<url>
    <loc>{{ $story->public_url }}</loc>
    <changefreq>{{ $story->is_recent_created ? 'hourly' : 'never' }}</changefreq>
    <priority>0.8</priority>
    <lastmod>{{ ($story->updated_at ?? now())->format('Y-m-d\TH:i:sP') }}</lastmod>

    @if ($story->images())
        @foreach ($story->images() as $image)
            <image:image>
                <image:loc>{{ $image->getUrl() }}</image:loc>
                <image:caption>{{ $image->getCustomProperty('caption') ?? $story->title }}</image:caption>
                <image:title>{{ $image->getCustomProperty('alt') ?? $story->title }}</image:title>
            </image:image>
        @endforeach
    @endif

    @if ($story->videos())
        @foreach ($story->videos() as $video)
            <video:video>
                <video:content_loc>{{ $video->getUrl() }}</video:content_loc>
                <video:title>{{ $video->getCustomProperty('title') ?? $story->title }}</video:title>
                <video:description>{{ $video->getCustomProperty('description') ?? $story->brief }}</video:description>
                <video:publication_date>{{ $video->created_at->format('Y-m-d\TH:i:sP') }}</video:publication_date>
            </video:video>
        @endforeach
    @endif

    @if ($story->audios())
        @foreach ($story->audios() as $audio)
            <audio:audio>
                <audio:content_loc>{{ $audio->getUrl() }}</audio:content_loc>
                <audio:title>{{ $audio->getCustomProperty('title') ?? $story->title }}</audio:title>
                <audio:description>{{ $audio->getCustomProperty('description') ?? $story->brief }}</audio:description>
                <audio:publication_date>{{ $audio->created_at->format('Y-m-d\TH:i:sP') }}</audio:publication_date>
            </audio:audio>
        @endforeach
    @endif

    <story:story>
        <story:publication>
            <news:name>{{ config('app.name') }}</news:name>
            <news:language>{{ $story->lannguage->code }}</news:language>
        </news:publication>
        <news:title>{{ $story->title }}</news:title>
        <news:publication_date>{{ $story->created_at->format('Y-m-d\TH:i:sP') }}</news:publication_date>
        <news:keywords>{{ $keywords }}</news:keywords>
    </news:news>
</url>
