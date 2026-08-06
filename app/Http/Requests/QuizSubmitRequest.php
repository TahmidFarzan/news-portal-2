<?php

namespace App\Http\Requests;

use App\Helpers\QuizHelper;
use App\Models\Quiz;
use App\Models\QuizQuestion;
use App\Models\QuizQuestionOption;
use Illuminate\Foundation\Http\FormRequest;

class QuizSubmitRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:200'],
            'phone'    => ['nullable', 'string', 'max:30'],
            'email'    => ['nullable', 'email', 'max:200'],
            'address'  => ['nullable', 'string', 'max:500'],
            'duration' => ['required', 'integer', 'min:0'],

            'answers'                          => ['required', 'array', 'min:1'],
            'answers.*.question_id'            => ['required', 'integer'],
            'answers.*.selected_option_id'     => ['nullable', 'integer'],
            'answers.*.selected_option_ids'    => ['nullable', 'array'],
            'answers.*.selected_option_ids.*'  => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('form-requests.quiz_submit.name.required'),
            'name.string'   => __('form-requests.quiz_submit.name.string'),
            'name.max'      => __('form-requests.quiz_submit.name.max'),

            'phone.string' => __('form-requests.quiz_submit.phone.string'),
            'phone.max'    => __('form-requests.quiz_submit.phone.max'),

            'email.email' => __('form-requests.quiz_submit.email.email'),
            'email.max'   => __('form-requests.quiz_submit.email.max'),

            'address.string' => __('form-requests.quiz_submit.address.string'),
            'address.max'    => __('form-requests.quiz_submit.address.max'),

            'duration.required' => __('form-requests.quiz_submit.duration.required'),
            'duration.integer'  => __('form-requests.quiz_submit.duration.integer'),
            'duration.min'      => __('form-requests.quiz_submit.duration.min'),

            'answers.required' => __('form-requests.quiz_submit.answers.required'),
            'answers.array'    => __('form-requests.quiz_submit.answers.array'),
            'answers.min'      => __('form-requests.quiz_submit.answers.min'),

            'answers.*.question_id.required' => __('form-requests.quiz_submit.answers.question_id.required'),
            'answers.*.question_id.integer'  => __('form-requests.quiz_submit.answers.question_id.integer'),

            'answers.*.selected_option_id.integer'    => __('form-requests.quiz_submit.answers.selected_option_id.integer'),
            'answers.*.selected_option_ids.array'     => __('form-requests.quiz_submit.answers.selected_option_ids.array'),
            'answers.*.selected_option_ids.*.integer' => __('form-requests.quiz_submit.answers.selected_option_ids.integer'),
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            $hasPhone = !empty($data['phone']);
            $hasEmail = !empty($data['email']);

            if (!$hasPhone && !$hasEmail) {
                $validator->errors()->add(
                    'phone',
                    __('form-requests.quiz_submit.phone_or_email.required')
                );
                $validator->errors()->add(
                    'email',
                    __('form-requests.quiz_submit.phone_or_email.required')
                );
            }

            $quiz = $this->route('quiz') ?? $this->getQuizFromSlug();

            if (!$quiz) {
                $validator->errors()->add('quiz', __('form-requests.quiz_submit.quiz.not_found'));
                return;
            }

            $quizQuestions = QuizQuestion::where('quiz_id', $quiz->id)
                ->get(['id', 'answer_type'])
                ->keyBy('id');

            if ($quizQuestions->isEmpty()) {
                $validator->errors()->add('answers', __('form-requests.quiz_submit.answers.no_questions'));
                return;
            }

            $quizQuestionIds = $quizQuestions->keys()->map(fn ($id) => (int) $id)->all();

            $submittedQuestionIds = collect($data['answers'] ?? [])
                ->pluck('question_id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $missingQuestions = array_diff($quizQuestionIds, $submittedQuestionIds);
            if (!empty($missingQuestions)) {
                $validator->errors()->add(
                    'answers',
                    __('form-requests.quiz_submit.answers.incomplete')
                );
            }

            $extraQuestions = array_diff($submittedQuestionIds, $quizQuestionIds);
            if (!empty($extraQuestions)) {
                $validator->errors()->add(
                    'answers',
                    __('form-requests.quiz_submit.answers.invalid_question')
                );
            }

            foreach ($data['answers'] ?? [] as $index => $answer) {
                $questionId = (int) ($answer['question_id'] ?? 0);

                if (!$quizQuestions->has($questionId)) {
                    $validator->errors()->add(
                        "answers.{$index}.question_id",
                        __('form-requests.quiz_submit.answers.question_id.invalid')
                    );
                    continue;
                }

                $question = $quizQuestions->get($questionId);

                if ($question->answer_type === QuizHelper::QUESTION_ANSWER_TYPE_MULTIPLE) {
                    $selectedOptionIds = collect($answer['selected_option_ids'] ?? [])
                        ->map(fn ($id) => (int) $id)
                        ->filter()
                        ->values()
                        ->all();

                    if (empty($selectedOptionIds)) {
                        $validator->errors()->add(
                            "answers.{$index}.selected_option_ids",
                            __('form-requests.quiz_submit.answers.selected_option_ids.required')
                        );
                        continue;
                    }

                    foreach ($selectedOptionIds as $optionId) {
                        $optionExists = QuizQuestionOption::where('id', $optionId)
                            ->where('quiz_question_id', $questionId)
                            ->exists();

                        if (!$optionExists) {
                            $validator->errors()->add(
                                "answers.{$index}.selected_option_ids",
                                __('form-requests.quiz_submit.answers.selected_option_ids.invalid')
                            );
                            break;
                        }
                    }
                } else {
                    $optionId = (int) ($answer['selected_option_id'] ?? 0);

                    if (!$optionId) {
                        $validator->errors()->add(
                            "answers.{$index}.selected_option_id",
                            __('form-requests.quiz_submit.answers.selected_option_id.required')
                        );
                        continue;
                    }

                    $optionExists = QuizQuestionOption::where('id', $optionId)
                        ->where('quiz_question_id', $questionId)
                        ->exists();

                    if (!$optionExists) {
                        $validator->errors()->add(
                            "answers.{$index}.selected_option_id",
                            __('form-requests.quiz_submit.answers.selected_option_id.invalid')
                        );
                    }
                }
            }
        });
    }

    protected function getQuizFromSlug(): ?Quiz
    {
        $slug = $this->route('slug');

        if (!$slug) {
            return null;
        }

        return Quiz::where('slug', $slug)->first();
    }
}
