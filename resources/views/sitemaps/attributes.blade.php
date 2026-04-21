<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
                            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    @foreach ($records as $record)
        <url>
            <loc>{{ $record->public_url }}</loc>
            <changefreq>{{ $record->is_recent_created ? "daily" : "never" }}</changefreq>
            <priority>0.8</priority>
            <lastmod>{{ $record->created_at->format('Y-m-d\TH:i:sP') }}</lastmod>

            @if($record->language && $record->language?->code)
                <language>{{ $record->language->code }}</language>
            @endif
        </url>
    @endforeach
</urlset>
