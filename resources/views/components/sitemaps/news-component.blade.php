@php
    $keywords = $news->seo_keywords;

    foreach ($news->tags()->get() as $tag) {
        $keywords = $keywords . ',' . $tag->name;
    }
@endphp
<url>
    <loc>{{ $news->public_url }}</loc>
    <changefreq>{{ $news->is_recent_created ? 'hourly' : 'never' }}</changefreq>
    <priority>0.8</priority>
    <lastmod>{{ ($news->updated_at ?? now())->format('Y-m-d\TH:i:sP') }}</lastmod>

    @if ($news->images())
        @foreach ($news->images() as $image)
            <image:image>
                <image:loc>{{ $image->getUrl() }}</image:loc>
                <image:caption>{{ $image->getCustomProperty('caption') ?? $news->title }}</image:caption>
                <image:title>{{ $image->getCustomProperty('alt') ?? $news->title }}</image:title>
            </image:image>
        @endforeach
    @endif

    @if ($news->videos())
        @foreach ($news->videos() as $video)
            <video:video>
                <video:content_loc>{{ $video->getUrl() }}</video:content_loc>
                <video:title>{{ $video->getCustomProperty('title') ?? $news->title }}</video:title>
                <video:description>{{ $video->getCustomProperty('description') ?? $news->brief }}</video:description>
                <video:publication_date>{{ $video->created_at->format('Y-m-d\TH:i:sP') }}</video:publication_date>
            </video:video>
        @endforeach
    @endif

    @if ($news->audios())
        @foreach ($news->audios() as $audio)
            <audio:audio>
                <audio:content_loc>{{ $audio->getUrl() }}</audio:content_loc>
                <audio:title>{{ $audio->getCustomProperty('title') ?? $news->title }}</audio:title>
                <audio:description>{{ $audio->getCustomProperty('description') ?? $news->brief }}</audio:description>
                <audio:publication_date>{{ $audio->created_at->format('Y-m-d\TH:i:sP') }}</audio:publication_date>
            </audio:audio>
        @endforeach
    @endif

    <news:news>
        <news:publication>
            <news:name>{{ config('app.name') }}</news:name>
            <news:language>{{ $news->language->code }}</news:language>
        </news:publication>
        <news:title>{{ $news->title }}</news:title>
        <news:publication_date>{{ $news->created_at->format('Y-m-d\TH:i:sP') }}</news:publication_date>
        <news:keywords>{{ $keywords }}</news:keywords>
    </news:news>
</url>
