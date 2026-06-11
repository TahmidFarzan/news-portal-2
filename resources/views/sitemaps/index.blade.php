<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>{{ route('sitemaps.categories') }}</loc>
        <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
    </sitemap>
    <sitemap>
        <loc>{{ route('sitemaps.tags') }}</loc>
        <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
    </sitemap>
    <sitemap>
        <loc>{{ route('sitemaps.locations') }}</loc>
        <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
    </sitemap>
    <sitemap>
        <loc>{{ route('sitemaps.events') }}</loc>
        <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
    </sitemap>

    <sitemap>
        <loc>{{ route('sitemaps.contributors') }}</loc>
        <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
    </sitemap>

    <sitemap>
        <loc>{{ route('sitemaps.latest-news') }}</loc>
        <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
    </sitemap>

    <sitemap>
        <loc>{{ route('sitemaps.news') }}</loc>
        <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
    </sitemap>

    @foreach ($pages as $page)
        <sitemap>
            <loc>{{ $page->public_url }}</loc>
            <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
        </sitemap>
    @endforeach
</sitemapindex>
