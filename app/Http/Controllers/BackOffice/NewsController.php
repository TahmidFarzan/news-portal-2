<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\NewsGalleryImageRequest;
use App\Http\Requests\NewsGalleryImageSequenceUpdateRequest;
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

        Gate::authorize('view', $news);

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

        return $this->redirectAfterNewsSave($result);
    }

    public function update(NewsRequest $request, string $slug)
    {
        $news = $this->newsService->find($slug);

        Gate::authorize('update', $news);

        $result = $this->newsService->save($request, $news);

        return $this->redirectAfterNewsSave($result);
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
        $media = $this->newsService->galleryImageFind($news, $mediaSlug);

        Gate::authorize('update', $news);

        $result = $this->newsService->galleryImageUpdate($request, $news, $media);

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
    public function galleryImageUpdateSequence(NewsGalleryImageSequenceUpdateRequest $request, string $slug)
    {
        $news = $this->newsService->find($slug);

        Gate::authorize('update', $news);

        $result = $this->newsService->galleryImageUpdateSequence($news, $request);

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function galleryImageDelete(string $slug, string $mediaSlug)
    {
        $news  = $this->newsService->find($slug);
        $media = $this->newsService->galleryImageFind($news, $mediaSlug);

        Gate::authorize('update', $news);

        $result = $this->newsService->galleryImageDelete($news, $media);

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function newsPlacementByNewsIndex(string $slug)
    {
        $news = $this->newsService->find($slug);

        Gate::authorize('view', $news);

        $homeLeadNewsPlacements     = $this->newsService->newsPlacementHomeLead();
        $homeCategoryNewsPlacements = $this->newsService->newsPlacementHomeCategory($news->category_id);
        $categoryLeadNewsPlacements = $this->newsService->newsPlacementCategoryLead($news->category_id);

        return Inertia::render('back-office/newses/news-placement/Index', [
            'news'                   => $news,
            'homeLeadNewsPlacements'     => $homeLeadNewsPlacements,
            'homeCategoryNewsPlacements' => $homeCategoryNewsPlacements,
            'categoryLeadNewsPlacements' => $categoryLeadNewsPlacements,
        ]);
    }

    public function newsPlacementDetails(string $slug, string $newsPlacementSlug)
    {
        $news = $this->newsService->find($slug);

        Gate::authorize('view', $news);

        $newsPlacement = $this->newsService->newsPlacementFind($news, $newsPlacementSlug);

        return Inertia::render('back-office/newses/news-placement/Details', [
            'news'                   => $news,
            'newsPlacement'     => $newsPlacement,
        ]);
    }

    public function newsPlacementGenerateForNews(string $slug)
    {
        $news = $this->newsService->find($slug);

        Gate::authorize('update', $news);

        $result = $this->newsService->newsPlacementGenerateForNews($news);

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function newsPlacementUpdateForNews(Request $request, string $slug)
    {
        $news = $this->newsService->find($slug);

        Gate::authorize('update', $news);

        $result = $this->newsService->newsPlacementUpdateForNews($request, $news);

        return to_route('back-office.newses.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function newsPlacementDelete(string $slug, string $newsPlacementSlug)
    {
        $news = $this->newsService->find($slug);

        Gate::authorize('update', $news);

        $newsPlacement = $this->newsService->newsPlacementFind($news, $newsPlacementSlug);

        $result = $this->newsService->newsPlacementDelete($news, $newsPlacement);

        return back()->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    private function redirectAfterNewsSave(array $result)
    {
        if (empty($result['data']['news_slug'])) {
            return to_route('back-office.newses.index')->with('flash_message', [
                'message' => $result['message'],
                'status'  => $result['status'],
            ]);
        }

        return to_route('back-office.newses.news-placements.index', [
            'slug' => $result['data']['news_slug'],
        ])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

}
