<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewsGalleryImageRequest;
use App\Http\Requests\NewsRequest;
use App\Services\BackOffice\NewsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class NewsController extends Controller
{
    protected NewsService $newsService;

    public function __construct(NewsService $newsService)
    {
        $this->newsService = $newsService;
        $this->middleware(['auth', 'verified', 'user.role:admin,news_desk']);
    }

    public function index(Request $request)
    {
        $news = $this->newsService->new();
        Gate::authorize('viewAny', $news);

        $newses = $this->newsService->search($request);

        return Inertia::render('back-office/newses/Index', [
            'newses' => $newses,
        ]);
    }

    public function details(string $slug)
    {
        $news = $this->newsService->find($slug);
        $news = $this->newsService->loadRelations($news);

        Gate::authorize('create', $news);

        return Inertia::render('back-office/newses/Details', [
            'news' => $news,
        ]);
    }

    public function create()
    {
        $news = $this->newsService->new();
        Gate::authorize('create', $news);

        return Inertia::render('back-office/newses/Create', [
            'news' => $news,
        ]);
    }

    public function edit(string $slug)
    {
        $news = $this->newsService->find($slug);
        $news = $this->newsService->loadRelations($news);

        Gate::authorize('update', $news);

        return Inertia::render('back-office/newses/Create', [
            'news' => $news,
        ]);
    }

    public function save(NewsRequest $request)
    {
        $news = $this->newsService->new();
        Gate::authorize('create', $news);

        $result = $this->newsService->save($request, $news);

        return to_route('back-office.newses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(NewsRequest $request, string $slug)
    {
        $news = $this->newsService->find($slug);

        Gate::authorize('update', $news);

        $result = $this->newsService->save($request, $news);

        return to_route('back-office.newses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
    {
        $news = $this->newsService->find($slug);

        Gate::authorize('delete', $news);

        $result = $this->newsService->delete($news);

        return to_route('back-office.newses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function restore(string $slug)
    {
        $news = $this->newsService->find($slug);

        Gate::authorize('update', $news);

        $result = $this->newsService->restore($news);

        return to_route('back-office.newses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function galleryImageSave(NewsGalleryImageRequest $request, string $slug)
    {
        $news = $this->newsService->find($slug);

        Gate::authorize('update', $news);

        $result = $this->newsService->galleryImageSave($request, $news);

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function galleryImageUpdate(NewsGalleryImageRequest $request, string $slug, string $mediaSlug)
    {
        $news  = $this->newsService->find($slug);
        $media = $this->newsService->findMedia($news, $mediaSlug);

        Gate::authorize('update', $news);

        $result = $this->newsService->galleryImageUpdate($request, $news, $media);

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function galleryImageDelete(string $slug, string $mediaSlug)
    {
        $news  = $this->newsService->find($slug);
        $media = $this->newsService->findMedia($news, $mediaSlug);

        Gate::authorize('update', $news);

        $result = $this->newsService->galleryImageDelete($news, $media);

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

}
