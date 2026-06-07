<?php
namespace App\Http\Controllers;

use App\Services\PageService;
use Illuminate\Http\Request;
use Inertia\Inertia;

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

    public function tagNews(Request $request, $slug)
    {
        $tag  = $this->pageService->tag($slug);
        $news = $this->pageService->tagNews($request, $tag);

        if ($request->expectsJson()) {
            return response()->json([
                'news' => $news,
            ]);
        }

        return Inertia::render('TagNews', [
            'tag'  => $tag,
            'news' => $news,
        ]);
    }
}
