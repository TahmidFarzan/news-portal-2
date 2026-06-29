<?php
namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SurveyQuestionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "question" => ["required", "string"],
        ];
    }

    public function messages()
    {
        return [
            "question.required" => __("form-requests.survey_question.name.required"),
            "question.string"   => __("form-requests.survey_question.name.string"),
        ];
    }

}
