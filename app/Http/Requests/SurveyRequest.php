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
        return [
            "name"        => ["required", "string"],
            "brief"       => ["nullable"],
            "date"        => ["nullable"],
            "language_id" => ["required"],
        ];
    }

    public function messages()
    {
        return [
            "name.required"        => __("form-requests.survey.name.required"),
            "name.string"          => __("form-requests.survey.name.string"),

            "language_id.required" => __("form-requests.survey_question.language_id.required"),
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
                        __("form-requests.survey_question.language_id.not_found")
                    );
                }
            }
        });
    }
}
