<?php
namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\SurveyRequest;
use App\Http\Requests\SurveyQuestionRequest;
use App\Services\BackOffice\SurveyService;
use App\Services\BackOffice\SurveyQuestionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class SurveyController extends Controller
{
    protected SurveyService $surveyService;
    protected SurveyQuestionService $surveyQuestionService;

    public function __construct(SurveyService $surveyService, SurveyQuestionService $surveyQuestionService)
    {
        $this->surveyService         = $surveyService;
        $this->surveyQuestionService = $surveyQuestionService;
    }

    public function index(Request $request)
    {
        $survey = $this->surveyService->new();
        Gate::authorize('viewAny', $survey);

        $surveys = $this->surveyService->search($request);

        return Inertia::render('back-office/surveys/Index', [
            'surveys' => $surveys,
        ]);
    }

    public function details(string $slug)
    {
        $survey = $this->surveyService->find($slug);

        Gate::authorize('view', $survey);

        return Inertia::render('back-office/surveys/Details', [
            'survey' => $survey,
        ]);
    }

    public function create()
    {
        $survey = $this->surveyService->new();
        Gate::authorize('create', $survey);

        return Inertia::render('back-office/surveys/Create', [
            'survey' => $survey,
        ]);
    }

    public function edit(string $slug)
    {
        $survey = $this->surveyService->find($slug);

        Gate::authorize('update', $survey);

        return Inertia::render('back-office/surveys/Create', [
            'survey' => $survey,
        ]);
    }

    public function save(SurveyRequest $request)
    {
        $survey = $this->surveyService->new();
        Gate::authorize('create', $survey);

        $result = $this->surveyService->save($request, $survey);

        return to_route('back-office.surveys.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(SurveyRequest $request, string $slug)
    {
        $survey = $this->surveyService->find($slug);

        Gate::authorize('update', $survey);

        $result = $this->surveyService->save($request, $survey);

        return to_route('back-office.surveys.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function active(string $slug)
    {
        $survey = $this->surveyService->find($slug);

        Gate::authorize('delete', $survey);

        $result = $this->surveyService->active($survey);

        return to_route('back-office.surveys.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function inactive(string $slug)
    {
        $survey = $this->surveyService->find($slug);

        Gate::authorize('restore', $survey);

        $result = $this->surveyService->inactive($survey);

        return to_route('back-office.surveys.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
    {
        $survey = $this->surveyService->find($slug);

        Gate::authorize('forceDelete', $survey);

        $result = $this->surveyService->delete($survey);

        return to_route('back-office.surveys.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function surveyQuestionIndex(Request $request, string $slug)
    {
        $survey = $this->surveyService->find($slug);

        $surveyQuestion = $this->surveyQuestionService->new();
        Gate::authorize('viewAny', $surveyQuestion);

        $surveyQuestions = $this->surveyQuestionService->search($request, $survey);

        return Inertia::render('back-office/surveys/survey-questions/Index', [
            "survey" => $survey,
            'surveyQuestions' => $surveyQuestions,
        ]);
    }

    public function surveyQuestionDetails(string $slug, string $surveyQuestionSlug)
    {
        $survey = $this->surveyService->find($slug);
        $surveyQuestion = $this->surveyQuestionService->find($survey, $surveyQuestionSlug);

        Gate::authorize('view', $surveyQuestion);

        return Inertia::render('back-office/surveys/survey-questions/Details', [
            "survey" => $survey,
            'surveyQuestion' => $surveyQuestion,
        ]);
    }

    public function surveyQuestionCreate(string $slug)
    {
        $survey = $this->surveyService->find($slug);

        $surveyQuestion = $this->surveyQuestionService->new();

        Gate::authorize('create', $surveyQuestion);

        return Inertia::render('back-office/surveys/survey-questions/Create', [
            'survey'         => $survey,
            "surveyQuestion" => $surveyQuestion,
        ]);
    }

    public function surveyQuestionEdit(string $slug, string $surveyQuestionSlug)
    {
        $survey = $this->surveyService->find($slug);

        $surveyQuestion = $this->surveyQuestionService->find($survey, $surveyQuestionSlug);

        Gate::authorize('create', $surveyQuestion);

        return Inertia::render('back-office/surveys/survey-questions/Create', [
            'survey'         => $survey,
            "surveyQuestion" => $surveyQuestion,
        ]);
    }

    public function surveyQuestionSave(SurveyQuestionRequest $request, string $slug)
    {
        $survey = $this->surveyService->find($slug);

        $surveyQuestion = $this->surveyQuestionService->new();

        Gate::authorize('create', $surveyQuestion);

        $result = $this->surveyQuestionService->save($request, $survey, $surveyQuestion);

        if ($result["redirect_back_to_same_page"] == true) {
            return back()->with('flash_message', [
                'message' => $result['message'],
                'status'  => $result['status'],
            ]);
        }

        return to_route('back-office.surveys.survey-questions.index',["slug" => $slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function surveyQuestionUpdate(SurveyQuestionRequest $request, string $slug, string $surveyQuestionSlug)
    {
        $survey = $this->surveyService->find($slug);

        $surveyQuestion = $this->surveyQuestionService->find($survey, $surveyQuestionSlug);

        Gate::authorize('update', $surveyQuestion);

        $result = $this->surveyQuestionService->save($request,$survey, $surveyQuestion);


        if ($result["redirect_back_to_same_page"] == true) {
            return back()->with('flash_message', [
                'message' => $result['message'],
                'status'  => $result['status'],
            ]);
        }

        return to_route('back-office.surveys.survey-questions.index',["slug" => $slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }


    public function surveyQuestionDelete(string $slug, string $surveyQuestionSlug)
    {
        $survey = $this->surveyService->find($slug);
        $surveyQuestion = $this->surveyQuestionService->find($survey, $surveyQuestionSlug);

        Gate::authorize('delete', $surveyQuestion);

        $result = $this->surveyQuestionService->delete($surveyQuestion);

        return to_route('back-office.surveys.survey-questions.index',["slug" => $slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function surveyQuestionReorder(string $slug, Request $request)
    {
        $request->validate([
            'questions'                      => ['required', 'array', 'min:1'],
            'questions.*.slug'               => ['required', 'string'],
            'questions.*.position'           => ['required', 'integer', 'min:1'],
            'redirect_back_to_same_page'   => ['nullable', 'boolean'],
        ]);

        $survey = $this->surveyService->find($slug);

        $result = $this->surveyQuestionService->reorder(
            $survey,
            $request
        );

        if (($result['redirect_back_to_same_page'] ?? false) === true) {
            return back()->with('flash_message', [
                'message' => $result['message'],
                'status'  => $result['status'],
                'timestamp' => now()->timestamp,
            ]);
        }

        return to_route('back-office.surveys.survey-questions.index', [
            'slug'             => $survey->slug,
        ])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
            'timestamp' => now()->timestamp,
        ]);
    }

}
