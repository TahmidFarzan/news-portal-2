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

            'position' => ['nullable', 'string'],
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

            'position.string' => __('form-requests.google_ad.position.string'),
            'position.required' => __('form-requests.google_ad.position.required'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $data = $validator->getData();

            $type = $data['type'] ?? null;
            $position = $data['position'] ?? null;

            $types = [
                GoogleAdHelper::TYPE_SECTION,
                GoogleAdHelper::TYPE_SIDEBAR,
                GoogleAdHelper::TYPE_POPUP_HOME_PAGE,
                GoogleAdHelper::TYPE_POPUP_LATEST_PAGE,
                GoogleAdHelper::TYPE_POPUP_SEARCH_PAGE,
                GoogleAdHelper::TYPE_POPUP_VIDEO_PAGE,
                GoogleAdHelper::TYPE_POPUP_IMAGE_GALLERY_PAGE,
                GoogleAdHelper::TYPE_POPUP_CATEGORY_PAGE,
                GoogleAdHelper::TYPE_POPUP_TAG_PAGE,
                GoogleAdHelper::TYPE_POPUP_EVENT_PAGE,
                GoogleAdHelper::TYPE_POPUP_LOCATION_PAGE,
                GoogleAdHelper::TYPE_POPUP_NEWS_DETAILS_PAGE,
                GoogleAdHelper::TYPE_POPUP_CONTACT_PAGE,
                GoogleAdHelper::TYPE_POPUP_ABOUT_PAGE,
                GoogleAdHelper::TYPE_POPUP_OTHER_PAGE,
            ];

            if ($type && !in_array($type, $types, true)) {
                $validator->errors()->add(
                    'type',
                    __('form-requests.google_ad.type.not_exit')
                );

                return;
            }

            $isPopup = $type && str_contains($type, GoogleAdHelper::POPUP_LABEL);

            if (!$isPopup) {
                if (!$position) {
                    $validator->errors()->add(
                        'position',
                        __('form-requests.google_ad.position.required')
                    );

                    return;
                }

                $positions = [
                    GoogleAdHelper::POSITION_TOP,
                    GoogleAdHelper::POSITION_BOTTOM,
                    GoogleAdHelper::POSITION_BETWEEN,
                ];

                if (!in_array($position, $positions, true)) {
                    $validator->errors()->add(
                        'position',
                        __('form-requests.google_ad.position.not_exit')
                    );
                }
            } elseif ($position) {
                $validator->errors()->add(
                    'position',
                    __('form-requests.google_ad.position.not_allowed_for_popup')
                );
            }
        });
    }
}
