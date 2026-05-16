<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach (range(1, $lastPage) as $page)
        <sitemap>
            <loc>{{ $routeUrl . '?page=' . $page }}</loc>
            <lastmod>{{ $page == 1 ? now()->format('Y-m-d\TH:i:sP') : now()->subWeeks(1)->format('Y-m-d\TH:i:sP') }}
            </lastmod>
        </sitemap>
    @endforeach
</sitemapindex>
