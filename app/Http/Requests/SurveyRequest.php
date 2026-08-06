<?php
namespace App\Http\Requests;

use App\Models\Language;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SurveyRequest extends FormRequest
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
        $isUpdate = $this->route('slug') !== null;

        $rules =[
            "name"        => ["required", "string"],
            "brief"       => ["nullable"],
            'start_date'  => [
                'required',
                'date',
            ],

            'end_date'    => [
                'required',
                'date',
                'after_or_equal:start_date',
            ],
            "language_id" => ["required"],
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
            ];
        }
        return $rules;
    }

    public function messages()
    {
        return [
            "name.required"           => __("form-requests.survey.name.required"),
            "name.string"             => __("form-requests.survey.name.string"),

            "language_id.required"    => __("form-requests.survey.language_id.required"),

            'start_date.required'     => __('form-requests.survey.start_date.required'),
            'start_date.date'         => __('form-requests.survey.start_date.date'),

            'end_date.required'       => __('form-requests.survey.end_date.required'),
            'end_date.date'           => __('form-requests.survey.end_date.date'),
            'end_date.after_or_equal' => __('form-requests.survey.end_date.after_or_equal'),

            'questions.*.question.required' => __('form-requests.survey.question.required'),
            'questions.*.question.string' => __('form-requests.survey.question.string'),

            'questions.*.position.integer' => __('form-requests.survey.position.integer'),
            'questions.*.position.min' => __('form-requests.survey.position.min'),
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            if (! empty($data["language_id"])) {
                $languageQuery = Language::where("id", $data["language_id"]);

                if ($languageQuery->count() === 0) {
                    $validator->errors()->add(
                        'language_id',
                        __("form-requests.survey.language_id.not_found")
                    );
                }
            }
        });
    }
}
