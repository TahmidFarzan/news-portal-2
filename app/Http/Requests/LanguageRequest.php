<?php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LanguageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'    => ['required', 'string', 'max:255'],
            'code'    => ['required', 'string', 'max:255', Rule::unique('languages')->ignore($this->route('slug'), 'slug')],
            'details' => ['nullable'],
        ];
    }

    public function messages()
    {
        return [
            'name.required' => __('form-requests.language.name.required'),
            'name.string'   => __('form-requests.language.name.string'),
            'name.max'      => __('form-requests.language.name.max'),

            'code.required' => __('form-requests.language.code.required'),
            'code.string'   => __('form-requests.language.code.string'),
            'code.max'      => __('form-requests.language.code.max'),
            'code.unique'   => __('form-requests.language.code.unique'),
        ];
    }
}
