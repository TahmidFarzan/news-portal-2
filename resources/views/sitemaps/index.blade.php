<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>{{ route('sitemaps.newses.categories') }}</loc>
        <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
	</sitemap>
    <sitemap>
        <loc>{{ route('sitemaps.newses.tags') }}</loc>
        <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
	</sitemap>
    <sitemap>
        <loc>{{ route('sitemaps.newses.locations') }}</loc>
        <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
	</sitemap>
    <sitemap>
        <loc>{{ route('sitemaps.newses.events') }}</loc>
        <lastmod>{{ now()->format('Y-m-d\TH:i:sP') }}</lastmod>
	</sitemap>
</sitemapindex>
