<?php
namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class NewsGalleryImageRequest extends FormRequest
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
            'image'        => ['nullable', "image", "mimes:jpg,jpeg,png,webp"],
            'order_column' => ['nullable', 'integer'],
            'caption'      => ['nullable', 'string', 'max:255'],
            'alt'          => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_column.integer' => __('form-requests.news.gallery_image.order_column.integer'),

            'caption.string'       => __('form-requests.news.gallery_image.caption.string'),
            'caption.max'          => __('form-requests.news.gallery_image.caption.max'),

            'alt.string'           => __('form-requests.news.gallery_image.alt.string'),
            'alt.max'              => __('form-requests.news.gallery_image.alt.max'),

            "image.image"          => __("form-requests.news.gallery_image.image.image"),
            "image.mimes"          => __("form-requests.news.gallery_image.image.image"),
            "image.dimensions"     => __("form-requests.news.gallery_image.image.dimensions"),
        ];
    }

    public function withValidator($validator): void
    {
        $isUpdate = $this->route('mediaSlug') ? true : false;

        $validator->after(function ($validator) use ($isUpdate) {
            $data = $validator->getData();

            if (!$isUpdate && !$this->hasFile('image')) {
                $validator->errors()->add('image', __("form-requests.news.image.gallery_image.image.required"));
            }
        });
    }
}
