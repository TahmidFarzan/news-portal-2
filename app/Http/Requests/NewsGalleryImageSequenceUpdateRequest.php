<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class NewsGalleryImageSequenceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sequence'   => ['required', 'array', 'min:1'],
            'sequence.*' => ['required', 'integer', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'sequence.required'   => __('form-requests.news.gallery_image_sequence.sequence.required'),
            'sequence.array'      => __('form-requests.news.gallery_image_sequence.sequence.array'),
            'sequence.min'        => __('form-requests.news.gallery_image_sequence.sequence.min'),

            'sequence.*.required' => __('form-requests.news.gallery_image_sequence.sequence.required'),
            'sequence.*.integer'  => __('form-requests.news.gallery_image_sequence.sequence.integer'),
            'sequence.*.distinct' => __('form-requests.news.gallery_image_sequence.sequence.distinct'),
        ];
    }
}
