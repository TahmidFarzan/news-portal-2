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
            "media"   => ["nullable", "string"],
        ];
    }

    public function messages()
    {
        return [
            'alt.string'     => __('form-requests.media_quick.alt.string'),
            'caption.string' => __('form-requests.media_quick.caption.string'),
        ];
    }

    public function withValidator($validator): void
    {
        $isUpdate = $this->route('slug') ? true : false;

        $validator->after(function ($validator) use ($isUpdate) {
            $data = $validator->getData();

            if (! $isUpdate && ! $this->hasFile('media')) {
                $validator->errors()->add('media', __("form-requests.media_quick.media.required"));
            }
        });
    }
}
