<?php

namespace App\Http\Requests;

use App\Models\Language;
use App\Models\BreakingNews;
use App\Models\News;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BreakingNewsRequest extends FormRequest
{
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
            "news_id"                      => ["nullable"],
            "language_id"                       => ["required"],
            "title"                             => ["required"],
           
        ];
    }

    public function messages(): array
    {
        return [
            "title.required"                         => __("form-requests.news.title.required"),
            "title.string"                           => __("form-requests.news.title.string"),
            "title.max"                              => __("form-requests.news.title.max"),

            "language_id.required"                   => __("form-requests.news.language_id.required"),
        ];
    }

    public function withValidator($validator): void
    {
        $breakingNews = BreakingNews::where('slug', $this->route('slug'))->first();

        $isUpdate = $this->route('slug') ? true : false;

        $validator->after(function ($validator) use ($breakingNews, $isUpdate) {
            $data = $validator->getData();

            if (! empty($data['title'])) {
                $dates = [
                    now()->format('Y-m-d'),
                    now()->subDay()->format('Y-m-d'),
                ];

                foreach ($dates as $date) {
                    $newsesQuery = BreakingNews::where('title', $data['title'])
                        ->whereDate('created_at', $date);

                    if ($breakingNews) {
                        $newsesQuery->where('id', '!=', $breakingNews->id);
                    }

                    if ($newsesQuery->exists()) {
                        $validator->errors()->add(
                            'title',
                            __("form-requests.news.title.unique")
                        );

                        break;
                    }
                }
            }

            if (! empty($data["language_id"])) {
                if (! Language::where("id", $data["language_id"])->exists()) {
                    $validator->errors()->add(
                        'language_id',
                        __("form-requests.news.language_id.not_found")
                    );
                }
            }

            if (! empty($data["news_id"])) {
                if (! News::where("id", $data["news_id"])->exists()) {
                    $validator->errors()->add(
                        'news_id',
                        __("form-requests.news.news_id.not_found")
                    );
                }

                if(! BreakingNews::where("news_id", $data["news_id"])->exists()){
                    $validator->errors()->add(
                        'news_id',
                        __("form-requests.news.news_id.already_sync")
                    );
                }
            }

        });
    }

}
