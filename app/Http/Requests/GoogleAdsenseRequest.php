<?php
namespace App\Http\Requests;

use App\Helpers\GoogleAdsenseHelper;
use App\Models\GoogleAdsense;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GoogleAdsenseRequest extends FormRequest
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
            'name.required'     => __('form-requests.google_adsense.name.required'),
            'name.string'       => __('form-requests.google_adsense.name.string'),
            'name.max'          => __('form-requests.google_adsense.name.max'),

            'slot_id.required'  => __('form-requests.google_adsense.slot_id.required'),
            'slot_id.string'    => __('form-requests.google_adsense.slot_id.string'),
            'slot_id.max'       => __('form-requests.google_adsense.slot_id.max'),

            'type.required'     => __('form-requests.google_adsense.type.required'),
            'position.required' => __('form-requests.google_adsense.position.required'),
        ];
    }

    public function withValidator($validator)
    {
        $googleAdsense = GoogleAdsense::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($googleAdsense) {
            $aVData = $validator->getData();

            if (isset($aVData["type"])) {
                $typeArray = [
                    GoogleAdsenseHelper::TYPE_SECTION,
                    GoogleAdsenseHelper::TYPE_SIDEBAR,
                ];
                if (! in_array($aVData["type"], $typeArray)) {
                    $validator->errors()->add(
                        'type', __("form-requests.google_adsense.type.not_exit"),
                    );
                }
            }

            if (isset($aVData["position"])) {
                $positionArray = [
                    GoogleAdsenseHelper::POSITION_TOP,
                    GoogleAdsenseHelper::POSITION_BOTTOM,
                    GoogleAdsenseHelper::POSITION_BETWEEN,
                ];
                if (! in_array($aVData["position"], $positionArray)) {
                    $validator->errors()->add(
                        'position', __("form-requests.google_adsense.position.not_exit"),
                    );
                }
            }
        });
    }
}
