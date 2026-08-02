<?php

namespace App\Http\Requests;

use App\Helpers\QuizHelper;
use App\Models\Language;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->route('slug') !== null;

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                // Rule::unique('quizzes','name')->ignore($this->route('slug'), 'slug'),
            ],

            'language_id' => [
                'required',
                'integer',
                'min:1',
            ],

            'brief' => [
                'nullable',
                'string',
            ],

            'start_date' => [
                'required',
                'date',
            ],

            'end_date' => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'show_bellow_event' => [
                'required',
                'boolean',
            ],

            'show_result' => [
                'required',
                'boolean',
            ],

            'max_winner' => [
                'required',
                'min:1',
            ],
        ];

        if (! $isUpdate) {
            $rules += [
                'questions' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'questions.*.question' => [
                    'required',
                    'string',
                ],

                'questions.*.answer_type' => [
                    'required',
                    Rule::in([
                        QuizHelper::QUESTION_ANSWER_TYPE_SINGLE,
                        QuizHelper::QUESTION_ANSWER_TYPE_MULTIPLE,
                    ]),
                ],

                'questions.*.point' => [
                    'required',
                    'numeric',
                    'min:0',
                ],

                'questions.*.position' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],

                'questions.*.options' => [
                    'required',
                    'array',
                    'min:2',
                ],

                'questions.*.options.*.option' => [
                    'required',
                    'string',
                ],

                'questions.*.options.*.is_correct' => [
                    'required',
                    'boolean',
                ],

                'questions.*.options.*.position' => [
                    'nullable',
                    'integer',
                    'min:1',
                ],
            ];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => __('form-requests.quiz.name.required'),
            'name.string' => __('form-requests.quiz.name.string'),
            'name.max' => __('form-requests.quiz.name.max'),

            'language_id.required' => __('form-requests.quiz.language_id.required'),

            'brief.string' => __('form-requests.quiz.brief.string'),

            'start_date.required' => __('form-requests.quiz.start_date.required'),
            'start_date.date' => __('form-requests.quiz.start_date.date'),

            'end_date.required' => __('form-requests.quiz.end_date.required'),
            'end_date.date' => __('form-requests.quiz.end_date.date'),
            'end_date.after_or_equal' => __('form-requests.quiz.end_date.after_or_equal'),

            'is_active.required' => __('form-requests.quiz.is_active.required'),
            'is_active.boolean' => __('form-requests.quiz.is_active.boolean'),

            'show_bellow_event.required' => __('form-requests.quiz.show_bellow_event.required'),
            'show_bellow_event.boolean' => __('form-requests.quiz.show_bellow_event.boolean'),

            'show_result.required' => __('form-requests.quiz.show_result.required'),
            'show_result.boolean' => __('form-requests.quiz.show_result.boolean'),

            'max_winner.required' => __('form-requests.quiz.max_winner.required'),
            'max_winner.boolean' => __('form-requests.quiz.max_winner.min'),

            'questions.required' => __('form-requests.quiz.questions.required'),
            'questions.array' => __('form-requests.quiz.questions.array'),
            'questions.min' => __('form-requests.quiz.questions.min'),

            'questions.*.question.required' => __('form-requests.quiz.question.required'),
            'questions.*.question.string' => __('form-requests.quiz.question.string'),

            'questions.*.answer_type.required' => __('form-requests.quiz.answer_type.required'),
            'questions.*.answer_type.in' => __('form-requests.quiz.answer_type.in'),

            'questions.*.point.required' => __('form-requests.quiz.point.required'),
            'questions.*.point.numeric' => __('form-requests.quiz.point.numeric'),
            'questions.*.point.min' => __('form-requests.quiz.point.min'),

            'questions.*.position.integer' => __('form-requests.quiz.position.integer'),
            'questions.*.position.min' => __('form-requests.quiz.position.min'),

            'questions.*.options.required' => __('form-requests.quiz.options.required'),
            'questions.*.options.array' => __('form-requests.quiz.options.array'),
            'questions.*.options.min' => __('form-requests.quiz.options.min'),

            'questions.*.options.*.option.required' => __('form-requests.quiz.option.required'),
            'questions.*.options.*.option.string' => __('form-requests.quiz.option.string'),

            'questions.*.options.*.is_correct.required' => __('form-requests.quiz.is_correct.required'),
            'questions.*.options.*.is_correct.boolean' => __('form-requests.quiz.is_correct.boolean'),

            'questions.*.options.*.position.integer' => __('form-requests.quiz.position.integer'),
            'questions.*.options.*.position.min' => __('form-requests.quiz.position.min'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            $data = $validator->getData();

            if (! empty($data['language_id']) && ! Language::whereKey($data['language_id'])->exists()) {
                $validator->errors()->add(
                    'language_id',
                    __('form-requests.quiz.language_id.not_found')
                );
            }

            if ($this->route('slug')) {
                return;
            }

            foreach ($data['questions'] ?? [] as $index => $question) {

                $options = collect($question['options'] ?? []);

                $correctAnswers = $options
                    ->filter(function ($option) {
                        return filter_var(
                            $option['is_correct'] ?? false,
                            FILTER_VALIDATE_BOOLEAN
                        );
                    })
                    ->count();

                $duplicates = $options
                    ->pluck('option')
                    ->filter()
                    ->map(fn ($option) => mb_strtolower(trim($option)))
                    ->duplicates();

                if ($duplicates->isNotEmpty()) {
                    $validator->errors()->add(
                        "questions.{$index}.options",
                        __('form-requests.quiz.option.duplicate')
                    );
                }

                $answerType = $question['answer_type'] ?? null;

                if (
                    $answerType === QuizHelper::QUESTION_ANSWER_TYPE_SINGLE &&
                    $correctAnswers !== 1
                ) {
                    $validator->errors()->add(
                        "questions.{$index}.options",
                        __('form-requests.quiz.answer.single')
                    );
                }

                if (
                    $answerType === QuizHelper::QUESTION_ANSWER_TYPE_MULTIPLE &&
                    $correctAnswers < 1
                ) {
                    $validator->errors()->add(
                        "questions.{$index}.options",
                        __('form-requests.quiz.answer.multiple')
                    );
                }
            }
        });
    }
}
