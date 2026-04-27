<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @php
        $url = route('sitemaps.newses.categories');

        switch ($routeFor) {
            case 'Category':
                $url = route('sitemaps.newses.categories');
                break;

            case 'Tag':
                $url = route('sitemaps.newses.tags');
                break;

            case 'Location':
                $url = route('sitemaps.newses.locations');
                break;

            case 'Event':
                $url = route('sitemaps.newses.locations');
                break;

            default:
                $url = route('sitemaps.newses.categories');
                break;
        }
    @endphp

    @foreach (range(1, $lastPage) as $page)
        <sitemap>
            <loc>{{ $url . '?page=' . $page }}</loc>
            <lastmod>{{ $page == 1 ? now()->format('Y-m-d\TH:i:sP') : now()->subWeeks(1)->format('Y-m-d\TH:i:sP') }}
            </lastmod>
        </sitemap>
    @endforeach
</sitemapindex>
