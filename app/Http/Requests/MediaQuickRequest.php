<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MediaQuickRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            "alt"     => ["nullable", "string"],
            "caption" => ["nullable", "string"],
            "media"   => ["required"],
        ];
    }

    public function messages()
    {
        return [
            'alt.string'     => __('form-requests.media_quick.alt.string'),
            'caption.string' => __('form-requests.media_quick.caption.string'),
            'media.required' => __('form-requests.media_quick.media.required'),
        ];
    }
}
