<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

use App\Services\PageService;

class PageController extends Controller
{

    protected PageService $pageService;

    public function __construct(PageService $pageService)
    {
        $this->pageService = $pageService;
    }

    public function home()
    {
        return Inertia::render('Home');
    }


    public function newsDetails($slug)
    {
        $news = $this->pageService->news($slug);

        return Inertia::render('NewsDetails', [
            'news' => $news,
        ]);
    }
}
