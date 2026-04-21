<?php
namespace App\Http\Controllers;

use App\Services\SitemapService;
use Illuminate\Http\Request;

class SitemapController extends Controller
{
    protected SitemapService $sitemapService;

    public function __construct(SitemapService $sitemapService)
    {
        $this->sitemapService = $sitemapService;
    }

    public function index()
    {
        return response()->view('sitemaps.index')
            ->header('Content-Type', 'application/xml');
    }

    public function categories(Request $request)
    {
        if ($request->filled('page')) {
            $records = $this->sitemapService->getCategories($request);
            return response()->view('sitemaps.attributes', compact('records'))
                ->header('Content-Type', 'application/xml');
        }

        $routeFor = 'Category';
        $lastPage = $this->sitemapService->getCategoriesLastPageNo();

        return response()->view('sitemaps.paginable-index', compact('lastPage', 'routeFor'))
            ->header('Content-Type', 'application/xml');
    }

}
