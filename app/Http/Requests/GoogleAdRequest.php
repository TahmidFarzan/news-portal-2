<?php
namespace App\Http\Requests;

use App\Helpers\GoogleAdHelper;
use App\Models\GoogleAd;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GoogleAdRequest extends FormRequest
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
            'name.required'     => __('form-requests.google_ad.name.required'),
            'name.string'       => __('form-requests.google_ad.name.string'),
            'name.max'          => __('form-requests.google_ad.name.max'),

            'slot_id.required'  => __('form-requests.google_ad.slot_id.required'),
            'slot_id.string'    => __('form-requests.google_ad.slot_id.string'),
            'slot_id.max'       => __('form-requests.google_ad.slot_id.max'),

            'type.required'     => __('form-requests.google_ad.type.required'),
            'position.required' => __('form-requests.google_ad.position.required'),
        ];
    }

    public function withValidator($validator)
    {
        $googleAd = GoogleAd::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($googleAd) {
            $aVData = $validator->getData();

            if (isset($aVData["type"])) {
                $typeArray = [
                    GoogleAdHelper::TYPE_SECTION,
                    GoogleAdHelper::TYPE_SIDEBAR,
                ];
                if (! in_array($aVData["type"], $typeArray)) {
                    $validator->errors()->add(
                        'type', __("form-requests.google_ad.type.not_exit"),
                    );
                }
            }

            if (isset($aVData["position"])) {
                $positionArray = [
                    GoogleAdHelper::POSITION_TOP,
                    GoogleAdHelper::POSITION_BOTTOM,
                    GoogleAdHelper::POSITION_BETWEEN,
                ];
                if (! in_array($aVData["position"], $positionArray)) {
                    $validator->errors()->add(
                        'position', __("form-requests.google_ad.position.not_exit"),
                    );
                }
            }
        });
    }
}
