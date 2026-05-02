<?xml version="1.0" encoding="UTF-8"?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @php
        $url = route('sitemaps.categories');

        switch ($routeFor) {
            case 'Category':
                $url = route('sitemaps.categories');
                break;

            case 'Tag':
                $url = route('sitemaps.tags');
                break;

            case 'Location':
                $url = route('sitemaps.locations');
                break;

            case 'Event':
                $url = route('sitemaps.events');
                break;

            case 'Contributor':
                $url = route('sitemaps.contributors');
                break;

            case 'News':
                $url = route('sitemaps.newses');
                break;

            default:
                $url = route('sitemaps.categories');
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
