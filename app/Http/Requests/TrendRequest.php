<?php
namespace App\Http\Requests;

use App\Models\Tag;
use App\Models\Trend;
use Illuminate\Foundation\Http\FormRequest;

class TrendRequest extends FormRequest
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

    public function rules()
    {
        return [
            "tag_id"   => ["required", "max:200"],
        ];
    }

    public function messages()
    {
        return [
            "tag_id.required"  => __("form-requests.trend.tag_id.required"),
            "tag_id.max"       => __("form-requests.trend.tag_id.max"),
        ];
    }

    public function withValidator($validator)
    {
        $trend = Trend::where("slug", $this->route('slug'))->firstOrFail();

        $validator->after(function ($validator) use ($trend) {
            $aVData = $validator->getData();

            if (isset($aVData['tag_id'])) {
                $tagFound = Tag::where('id', $aVData['tag_id'])->count();
                if ($tagFound == 0) {
                    $validator->errors()->add(
                        'tag_id', __("form-requests.trend.tag_id.not_found"),
                    );
                }

                $sameQuery = Trend::where("tag_id", $aVData["tag_id"]);

                if ($trend) {
                    $sameQuery->where("id", "!=", $trend->id);
                }

                if ($sameQuery->count() > 0) {
                    $validator->errors()->add(
                        'tag_id',
                        __("form-requests.trend.tag_id.unique")
                    );
                }
            }
        });
    }
}
