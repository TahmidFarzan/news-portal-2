<?php

namespace App\Http\Requests;

use App\Helpers\QuizHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuizQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isUpdate = $this->route('quizQuestionSlug') !== null;

        $rules = [
            'question' => [
                'required',
                'string',
            ],

            'answer_type' => [
                'required',
                Rule::in([
                    QuizHelper::QUESTION_ANSWER_TYPE_SINGLE,
                    QuizHelper::QUESTION_ANSWER_TYPE_MULTIPLE,
                ]),
            ],

            'point' => [
                'required',
                'numeric',
                'min:1',
            ],

            'position' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];

        if (! $isUpdate) {
            $rules += [
                'options' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'options.*.option' => [
                    'required',
                    'string',
                ],

                'options.*.is_correct' => [
                    'required',
                    'boolean',
                ],

                'options.*.position' => [
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
            'question.required' => __('form-requests.quiz_question.question.required'),
            'question.string' => __('form-requests.quiz_question.question.string'),

            'answer_type.required' => __('form-requests.quiz_question.answer_type.required'),
            'answer_type.in' => __('form-requests.quiz_question.answer_type.in'),

            'point.required' => __('form-requests.quiz_question.point.required'),
            'point.numeric' => __('form-requests.quiz_question.point.numeric'),
            'point.min' => __('form-requests.quiz_question.point.min'),

            'position.integer' => __('form-requests.quiz_question.position.integer'),
            'position.min' => __('form-requests.quiz_question.position.min'),

            'options.required' => __('form-requests.quiz_question.options.required'),
            'options.array' => __('form-requests.quiz_question.options.array'),
            'options.min' => __('form-requests.quiz_question.options.min'),

            'options.*.option.required' => __('form-requests.quiz_question.option.required'),
            'options.*.option.string' => __('form-requests.quiz_question.option.string'),

            'options.*.is_correct.required' => __('form-requests.quiz_question.is_correct.required'),
            'options.*.is_correct.boolean' => __('form-requests.quiz_question.is_correct.boolean'),

            'options.*.position.integer' => __('form-requests.quiz_question.position.integer'),
            'options.*.position.min' => __('form-requests.quiz_question.position.min'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {

            if ($this->route('quizQuestionSlug')) {
                return;
            }

            $data = $validator->getData();

            $options = collect($data['options'] ?? []);

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
                    'options',
                    __('form-requests.quiz_question.option.duplicate')
                );
            }

            $answerType = $data['answer_type'] ?? null;

            if ($answerType === QuizHelper::QUESTION_ANSWER_TYPE_SINGLE && $correctAnswers !== 1) {
                $validator->errors()->add(
                    'options',
                    __('form-requests.quiz_question.answer.single')
                );
            }

            if ($answerType === QuizHelper::QUESTION_ANSWER_TYPE_MULTIPLE && $correctAnswers < 1) {
                $validator->errors()->add(
                    'options',
                    __('form-requests.quiz_question.answer.multiple')
                );
            }
        });
    }
}
