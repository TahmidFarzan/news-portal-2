<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @for ($i = 0; $i < $lastPage; $i++)
        <sitemap>
            <loc>{{ $routeUrl . '?page=' . ($i + 1) }}</loc>
            <lastmod>{{ ($i + 1) == 1 ? now()->format('Y-m-d\TH:i:sP') : now()->subWeeks(1)->format('Y-m-d\TH:i:sP') }}
            </lastmod>
        </sitemap>
    @endfor
</sitemapindex>
