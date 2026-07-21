<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\LanguageRequest;
use App\Services\BackOffice\LanguageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class LanguageController extends Controller
{
    protected LanguageService $languageService;

    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    public function index(Request $request)
    {
        $language = $this->languageService->new();
        Gate::authorize('viewAny', $language);

        $languages = $this->languageService->search($request);

        return Inertia::render('back-office/languages/Index', [
            'languages' => $languages,
        ]);
    }


    public function setAsDefault(string $slug)
    {
        $language = $this->languageService->find($slug);

        Gate::authorize('update', $language);

        $result = $this->languageService->setAsDefault($language);

        return to_route('back-office.languages.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
