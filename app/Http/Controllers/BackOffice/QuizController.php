<?php

namespace App\Http\Controllers\BackOffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuizRequest;
use App\Http\Requests\QuizQuestionRequest;
use App\Http\Requests\QuizQuestionOptionRequest;
use App\Services\BackOffice\QuizService;
use App\Services\BackOffice\QuizQuestionService;
use App\Services\BackOffice\QuizQuestionOptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class QuizController extends Controller
{
    protected QuizService $quizService;
    protected QuizQuestionService $quizQuestionService;
    protected QuizQuestionOptionService $quizQuestionOptionService;

    public function __construct(QuizService $quizService, QuizQuestionService $quizQuestionService, QuizQuestionOptionService $quizQuestionOptionService)
    {
        $this->quizService         = $quizService;
        $this->quizQuestionService = $quizQuestionService;
        $this->quizQuestionOptionService = $quizQuestionOptionService;
    }

    public function index(Request $request)
    {
        $quiz = $this->quizService->new();
        Gate::authorize('viewAny', $quiz);

        $quizzes = $this->quizService->search($request);

        return Inertia::render('back-office/quizzes/Index', [
            'quizzes' => $quizzes,
        ]);
    }

    public function details(string $slug)
    {
        $quiz = $this->quizService->find($slug);

        Gate::authorize('view', $quiz);

        return Inertia::render('back-office/quizzes/Details', [
            'quiz' => $quiz,
        ]);
    }

    public function create()
    {
        $quiz = $this->quizService->new();
        Gate::authorize('create', $quiz);

        return Inertia::render('back-office/quizzes/Create', [
            'quiz' => $quiz,
        ]);
    }

    public function edit(string $slug)
    {
        $quiz = $this->quizService->find($slug);

        Gate::authorize('update', $quiz);

        return Inertia::render('back-office/quizzes/Create', [
            'quiz' => $quiz,
        ]);
    }

    public function save(QuizRequest $request)
    {
        $quiz = $this->quizService->new();
        Gate::authorize('create', $quiz);

        $result = $this->quizService->save($request, $quiz);

        return to_route('back-office.quizzes.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function update(QuizRequest $request, string $slug)
    {
        $quiz = $this->quizService->find($slug);

        Gate::authorize('update', $quiz);

        $result = $this->quizService->save($request, $quiz);

        return to_route('back-office.quizzes.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function active(string $slug)
    {
        $quiz = $this->quizService->find($slug);

        Gate::authorize('delete', $quiz);

        $result = $this->quizService->active($quiz);

        return to_route('back-office.quizzes.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function inactive(string $slug)
    {
        $quiz = $this->quizService->find($slug);

        Gate::authorize('restore', $quiz);

        $result = $this->quizService->inactive($quiz);

        return to_route('back-office.quizzes.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function delete(string $slug)
    {
        $quiz = $this->quizService->find($slug);

        Gate::authorize('forceDelete', $quiz);

        $result = $this->quizService->delete($quiz);

        return to_route('back-office.quizzes.index')->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function quizQuestionIndex(string $slug, Request $request)
    {
        $quiz = $this->quizService->find($slug);

        $quizQuestion = $this->quizQuestionService->new();
        Gate::authorize('viewAny', $quizQuestion);

        $quizQuestions = $this->quizQuestionService->search($quiz, $request);

        return Inertia::render('back-office/quizzes/quiz-questions/Index', [
            'quiz' => $quiz,
            'quizQuestions' => $quizQuestions,
        ]);
    }

    public function quizQuestionDetails(string $slug, string $quizQuestionSlug)
    {
        $quiz = $this->quizService->find($slug);
        $quizQuestion = $this->quizQuestionService->find($quiz, $quizQuestionSlug);

        Gate::authorize('view', $quizQuestion);

        return Inertia::render('back-office/quizzes/quiz-questions/Details', [
            'quiz' => $quiz,
            'quizQuestion' => $quizQuestion,
        ]);
    }

    public function quizQuestionCreate(string $slug)
    {

        $quiz = $this->quizService->find($slug);

        $quizQuestion = $this->quizQuestionService->new();
        Gate::authorize('create', $quizQuestion);

        return Inertia::render('back-office/quizzes/quiz-questions/Create', [
            'quiz' => $quiz,
            'quizQuestion' => $quizQuestion,
        ]);
    }

    public function quizQuestionEdit(string $slug, string $quizQuestionSlug)
    {
        $quiz = $this->quizService->find($slug);
        $quizQuestion = $this->quizQuestionService->find($quiz, $quizQuestionSlug);

        Gate::authorize('update', $quizQuestion);

        return Inertia::render('back-office/quizzes/quiz-questions/Create', [
            'quiz' => $quiz,
            'quizQuestion' => $quizQuestion,
        ]);
    }

    public function quizQuestionSave(string $slug, QuizQuestionRequest $request)
    {
        $quiz = $this->quizService->find($slug);

        $quizQuestion = $this->quizQuestionService->new();

        Gate::authorize('create', $quizQuestion);

        $result = $this->quizQuestionService->save($quiz, $request, $quizQuestion);

        return to_route('back-office.quizzes.quiz-questions.index', ['slug' => $quiz->slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function quizQuestionUpdate(string $slug, QuizQuestionRequest $request,  string $quizQuestionSlug)
    {
        $quiz = $this->quizService->find($slug);
        $quizQuestion = $this->quizQuestionService->find($quiz, $quizQuestionSlug);

        Gate::authorize('update', $quizQuestion);

        $result = $this->quizQuestionService->save($quiz, $request, $quizQuestion);

        return to_route('back-office.quizzes.quiz-questions.index', ['slug' => $quiz->slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function quizQuestionDelete(string $slug, string $quizQuestionSlug)
    {
        $quiz = $this->quizService->find($slug);
        $quizQuestion = $this->quizQuestionService->find($quiz, $quizQuestionSlug);

        Gate::authorize('delete', $quizQuestion);

        $result = $this->quizQuestionService->delete($quiz, $quizQuestion);

        return to_route('back-office.quizzes.quiz-questions.index', ['slug' => $quiz->slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function quizQuestionOptionIndex(string $slug, string $quizQuestionSlug, Request $request)
    {
        $quiz = $this->quizService->find($slug);
        $quizQuestion = $this->quizQuestionService->find($quiz, $quizQuestionSlug);

        $quizQuestionOption = $this->quizQuestionOptionService->new();
        Gate::authorize('viewAny', $quizQuestionOption);

        $quizQuestionOptions = $this->quizQuestionOptionService->search($quizQuestion, $request);

        return Inertia::render('back-office/quizzes/quiz-questions/quiz-question-options/Index', [
            'quiz' => $quiz,
            'quizQuestion' => $quizQuestion,
            'quizQuestionOptions' => $quizQuestionOptions,
        ]);
    }

    public function quizQuestionOptionDetails(string $slug, string $quizQuestionSlug, string $quizQuestionOptionSlug)
    {
        $quiz = $this->quizService->find($slug);
        $quizQuestion = $this->quizQuestionService->find($quiz, $quizQuestionSlug);
        $quizQuestionOption = $this->quizQuestionOptionService->find($quizQuestion, $quizQuestionOptionSlug);

        Gate::authorize('view', $quizQuestionOption);

        return Inertia::render('back-office/quizzes/quiz-questions/quiz-question-options/Details', [
            'quiz' => $quiz,
            'quizQuestion' => $quizQuestion,
            'quizQuestionOption' => $quizQuestionOption,
        ]);
    }

    public function quizQuestionOptionCreate(string $slug, string $quizQuestionSlug)
    {
        $quiz = $this->quizService->find($slug);
        $quizQuestion = $this->quizQuestionService->find($quiz, $quizQuestionSlug);

        $quizQuestionOption = $this->quizQuestionOptionService->new();
        Gate::authorize('create', $quizQuestion);

        return Inertia::render('back-office/quizzes/quiz-questions/quiz-question-options/Create', [
            'quiz' => $quiz,
            'quizQuestion' => $quizQuestion,
            'quizQuestionOption' => $quizQuestionOption,
        ]);
    }

    public function quizQuestionOptionEdit(string $slug, string $quizQuestionSlug, string $quizQuestionOptionSlug)
    {
        $quiz = $this->quizService->find($slug);
        $quizQuestion = $this->quizQuestionService->find($quiz, $quizQuestionSlug);
        $quizQuestionOption = $this->quizQuestionOptionService->find($quizQuestion, $quizQuestionOptionSlug);

        Gate::authorize('update', $quizQuestionOption);

        return Inertia::render('back-office/quizzes/quiz-questions/quiz-question-options/Create', [
            'quiz' => $quiz,
            'quizQuestion' => $quizQuestion,
            'quizQuestionOption' => $quizQuestionOption,
        ]);
    }

    public function quizQuestionOptionSave(string $slug,  string $quizQuestionSlug, QuizQuestionOptionRequest $request)
    {
        $quiz = $this->quizService->find($slug);
        $quizQuestion = $this->quizQuestionService->find($quiz, $quizQuestionSlug);

        $quizQuestionOption = $this->quizQuestionOptionService->new();

        Gate::authorize('create', $quizQuestionOption);

        $result = $this->quizQuestionOptionService->save($quizQuestion, $request, $quizQuestionOption);

        return to_route('back-office.quizzes.quiz-questions.quiz-question-options.index', ['slug' => $quiz->slug, 'quizQuestionSlug' => $quizQuestion->slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function quizQuestionOptionUpdate(string $slug, string $quizQuestionSlug, QuizQuestionOptionRequest $request, string $quizQuestionOptionSlug)
    {
        $quiz = $this->quizService->find($slug);
        $quizQuestion = $this->quizQuestionService->find($quiz, $quizQuestionSlug);
        $quizQuestionOption = $this->quizQuestionOptionService->find($quizQuestion, $quizQuestionOptionSlug);

        Gate::authorize('update', $quizQuestionOption);

        $result = $this->quizQuestionOptionService->save($quizQuestion, $request, $quizQuestionOption);

        if ($result["redirect_back_to_same_page"] == true) {
            return back()->with('flash_message', [
                'message' => $result['message'],
                'status'  => $result['status'],
            ]);
        }

        return to_route('back-office.quizzes.quiz-questions.quiz-question-options.index', ['slug' => $quiz->slug, 'quizQuestionSlug' => $quizQuestion->slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }

    public function quizQuestionOptionDelete(string $slug, string $quizQuestionSlug, string $quizQuestionOptionSlug)
    {
        $quiz = $this->quizService->find($slug);
        $quizQuestion = $this->quizQuestionService->find($quiz, $quizQuestionSlug);
        $quizQuestionOption = $this->quizQuestionOptionService->find($quizQuestion, $quizQuestionOptionSlug);

        Gate::authorize('delete', $quizQuestionOption);

        $result = $this->quizQuestionOptionService->delete($quizQuestion, $quizQuestionOption);

        return to_route('back-office.quizzes.quiz-questions.quiz-question-options.index', ['slug' => $quiz->slug, 'quizQuestionSlug' => $quizQuestion->slug])->with('flash_message', [
            'message' => $result['message'],
            'status'  => $result['status'],
        ]);
    }
}
