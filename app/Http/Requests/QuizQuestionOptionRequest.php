<?php

namespace App\Http\Requests;

use App\Models\QuizQuestion;
use Illuminate\Foundation\Http\FormRequest;

class QuizQuestionOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'option' => [
                'required',
                'string',
            ],

            'is_correct' => [
                'required',
                'boolean',
            ],

            'position' => [
                'nullable',
                'integer',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'option.required' => __('form-requests.quiz_question_option.option.required'),
            'option.string' => __('form-requests.quiz_question_option.option.string'),

            'is_correct.required' => __('form-requests.quiz_question_option.is_correct.required'),
            'is_correct.boolean' => __('form-requests.quiz_question_option.is_correct.boolean'),

            'position.integer' => __('form-requests.quiz_question_option.position.integer'),
            'position.min' => __('form-requests.quiz_question_option.position.min'),
        ];
    }
}
