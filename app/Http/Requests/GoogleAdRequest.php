<?php

namespace App\Http\Requests;

use App\Helpers\GoogleAdHelper;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GoogleAdRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],

            'ad_unit_code' => ['required', 'string', 'max:255'],

            'gpt_slot_id' => [
                'required',
                'string',
                'max:255',
            ],

            'ad_sizes' => ['nullable', 'array'],

            'ad_sizes.*' => ['array', 'size:2'],

            'ad_sizes.*.0' => ['required', 'integer', 'min:1'],

            'ad_sizes.*.1' => ['required', 'integer', 'min:1'],

            'type' => ['required', 'string'],

            'page' => ['required', 'string'],

            'placement' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('form-requests.google_ad.name.required'),
            'name.string' => __('form-requests.google_ad.name.string'),
            'name.max' => __('form-requests.google_ad.name.max'),

            'ad_unit_code.required' => __('form-requests.google_ad.ad_unit_code.required'),
            'ad_unit_code.string' => __('form-requests.google_ad.ad_unit_code.string'),
            'ad_unit_code.max' => __('form-requests.google_ad.ad_unit_code.max'),

            'gpt_slot_id.required' => __('form-requests.google_ad.gpt_slot_id.required'),
            'gpt_slot_id.string' => __('form-requests.google_ad.gpt_slot_id.string'),
            'gpt_slot_id.max' => __('form-requests.google_ad.gpt_slot_id.max'),

            'ad_sizes.array' => __('form-requests.google_ad.ad_sizes.array'),
            'ad_sizes.*.array' => __('form-requests.google_ad.ad_sizes.item_array'),
            'ad_sizes.*.size' => __('form-requests.google_ad.ad_sizes.item_size'),
            'ad_sizes.*.0.required' => __('form-requests.google_ad.ad_sizes.width.required'),
            'ad_sizes.*.0.integer' => __('form-requests.google_ad.ad_sizes.width.integer'),
            'ad_sizes.*.0.min' => __('form-requests.google_ad.ad_sizes.width.min'),
            'ad_sizes.*.1.required' => __('form-requests.google_ad.ad_sizes.height.required'),
            'ad_sizes.*.1.integer' => __('form-requests.google_ad.ad_sizes.height.integer'),
            'ad_sizes.*.1.min' => __('form-requests.google_ad.ad_sizes.height.min'),

            'type.required' => __('form-requests.google_ad.type.required'),
            'type.string' => __('form-requests.google_ad.type.string'),

            'page.required' => __('form-requests.google_ad.page.required'),
            'page.string' => __('form-requests.google_ad.page.string'),

            'placement.string' => __('form-requests.google_ad.placement.string'),
            'placement.required' => __('form-requests.google_ad.placement.required'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            $type = $data['type'] ?? null;
            $page = $data['page'] ?? null;
            $placement = $data['placement'] ?? null;

            $types = GoogleAdHelper::types()
                ->pluck('value')
                ->all();

            if ($type && !in_array($type, $types, true)) {
                $validator->errors()->add(
                    'type',
                    __('form-requests.google_ad.type.not_exit')
                );

                return;
            }

            $pages = GoogleAdHelper::pages()
                ->pluck('value')
                ->all();

            if ($page && !in_array($page, $pages, true)) {
                $validator->errors()->add(
                    'page',
                    __('form-requests.google_ad.page.not_exit')
                );

                return;
            }

            if ($type === GoogleAdHelper::TYPE_POPUP) {
                if ($placement) {
                    $validator->errors()->add(
                        'placement',
                        __('form-requests.google_ad.placement.not_allowed_for_popup')
                    );
                }

                return;
            }

            if (!$placement) {
                $validator->errors()->add(
                    'placement',
                    __('form-requests.google_ad.placement.required')
                );

                return;
            }

            $placements = GoogleAdHelper::placements($page, $type)
                ->pluck('value')
                ->all();

            if (!in_array($placement, $placements, true)) {
                $validator->errors()->add(
                    'placement',
                    __('form-requests.google_ad.placement.not_exit')
                );
            }
        });
    }
}
