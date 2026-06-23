<?php
namespace App\Http\Requests;

use App\Helpers\GoogleAdsenceHelper;
use App\Models\GoogleAdsence;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GoogleAdsenceRequest extends FormRequest
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
            'name'     => ['required', 'string', 'max:255'],
            'slot_id'  => ['required', 'string', 'max:255'],
            'type'     => ['required'],
            'position' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'     => __('form-requests.google_adsence.name.required'),
            'name.string'       => __('form-requests.google_adsence.name.string'),
            'name.max'          => __('form-requests.google_adsence.name.max'),

            'slot_id.required'  => __('form-requests.google_adsence.slot_id.required'),
            'slot_id.string'    => __('form-requests.google_adsence.slot_id.string'),
            'slot_id.max'       => __('form-requests.google_adsence.slot_id.max'),

            'type.required'     => __('form-requests.google_adsence.type.required'),
            'position.required' => __('form-requests.google_adsence.position.required'),
        ];
    }

    public function withValidator($validator)
    {
        $googleAdsence = GoogleAdsence::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($googleAdsence) {
            $aVData = $validator->getData();

            if (isset($aVData["type"])) {
                $typeArray = [
                    GoogleAdsenceHelper::TYPE_SECTION,
                    GoogleAdsenceHelper::TYPE_SIDEBAR,
                ];
                if (! in_array($aVData["type"], $typeArray)) {
                    $validator->errors()->add(
                        'type', __("form-requests.google_adsence.type.not_exit"),
                    );
                }
            }

            if (isset($aVData["position"])) {
                $positionArray = [
                    GoogleAdsenceHelper::POSITION_TOP,
                    GoogleAdsenceHelper::POSITION_BOTTOM,
                    GoogleAdsenceHelper::POSITION_BETWEEN,
                ];
                if (! in_array($aVData["position"], $positionArray)) {
                    $validator->errors()->add(
                        'position', __("form-requests.google_adsence.position.not_exit"),
                    );
                }
            }
        });
    }
}
