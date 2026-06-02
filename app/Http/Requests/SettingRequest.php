<?php
namespace App\Http\Requests;

use App\Helpers\SettingHelper;
use App\Models\Setting;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class SettingRequest extends FormRequest
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
            'group' => ['required', 'string'],
            'label' => ['required', 'string'],
            'type'  => ['required', 'string'],
            'value' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            "group.required" => __("form-requests.setting.group.required"),
            "group.string"   => __("form-requests.setting.group.string"),
            "group.max"      => __("form-requests.setting.group.max"),

            "label.required" => __("form-requests.setting.label.required"),
            "label.string"   => __("form-requests.setting.label.string"),
            "label.max"      => __("form-requests.setting.label.max"),

            "type.required"  => __("form-requests.setting.type.required"),
            "type.string"    => __("form-requests.setting.type.string"),
            "type.max"       => __("form-requests.setting.type.max"),

            "value.required" => __("form-requests.setting.value.required"),
            "value.string"   => __("form-requests.setting.value.string"),
            "value.max"      => __("form-requests.setting.value.max"),
        ];
    }

    public function withValidator($validator): void
    {
        $setting = Setting::where('slug', $this->route('slug'))->first();

        $isUpdate = $this->route('slug') ? true : false;

        $validator->after(function ($validator) use ($setting, $isUpdate) {
            $data = $validator->getData();

            if (! empty($data['group'])) {
                $allowedGroups = [
                    SettingHelper::GROUP_APP,
                    SettingHelper::GROUP_MENU,
                    SettingHelper::GROUP_SOCIAL_LINK,
                ];

                if (! in_array($data['group'], $allowedGroups, true)) {
                    $validator->errors()->add(
                        'group',
                        __('form-requests.setting.group.not_exit')
                    );
                }
            }

            if (! empty($data['type'])) {
                $allowedTypes = [
                    SettingHelper::TYPE_TEXT,
                    SettingHelper::TYPE_STRING,
                    SettingHelper::TYPE_BOOLEAN,
                    SettingHelper::TYPE_INTEGER,
                    SettingHelper::TYPE_FLOAT,
                    SettingHelper::TYPE_DECIMAL,
                    SettingHelper::TYPE_JSON,
                    SettingHelper::TYPE_ARRAY,
                    SettingHelper::TYPE_URL,
                    SettingHelper::TYPE_IMAGE,
                    SettingHelper::TYPE_COLOR,
                ];

                if (! in_array($data['type'], $allowedTypes, true)) {
                    $validator->errors()->add(
                        'type',
                        __('form-requests.setting.type.not_exit')
                    );
                }
            }

            if (! empty($data["group"]) && empty($data["key"])) {
                $sameSettingFound = Setting::where("group", $data["group"])->where("key", $data["key"]);
                if ($setting && $isUpdate) {
                    $sameSettingFound = $sameSettingFound->whereNot('id', $sameSettingFound->id);
                }
                $sameSettingFound = $sameSettingFound->count();
                if ($sameSettingFound) {
                    $validator->errors()->add(
                        'group',
                        __("form-requests.setting.group.unique")
                    );

                    $validator->errors()->add(
                        'key',
                        __("form-requests.setting.key.unique")
                    );
                }
            }

            if (! empty($data["type"]) && empty($data["value"])) {
                if (! $this->isValidSettingValue($data['type'], $data['value'])) {
                    $validator->errors()->add(
                        'value',
                        __('form-requests.setting.value.invalid_type')
                    );
                }
            }
        });
    }

    private function isValidSettingValue(string $type, mixed $value): bool
    {
        return match ($type) {
            SettingHelper::TYPE_TEXT,
            SettingHelper::TYPE_STRING  => is_string($value),

            SettingHelper::TYPE_BOOLEAN => is_bool($value)
            || filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null,

            SettingHelper::TYPE_INTEGER => is_int($value)
            || filter_var($value, FILTER_VALIDATE_INT) !== false,

            SettingHelper::TYPE_FLOAT   => is_float($value)
            || filter_var($value, FILTER_VALIDATE_FLOAT) !== false,

            SettingHelper::TYPE_DECIMAL => is_numeric($value)
            && preg_match('/^-?\d+(\.\d+)?$/', (string) $value),

            SettingHelper::TYPE_JSON    => $this->isValidJson($value),

            SettingHelper::TYPE_ARRAY   => is_array($value),

            SettingHelper::TYPE_URL     => is_string($value)
            && filter_var($value, FILTER_VALIDATE_URL) !== false,

            SettingHelper::TYPE_IMAGE   => $value instanceof UploadedFile
            && str_starts_with($value->getMimeType(), 'image/'),

            SettingHelper::TYPE_COLOR   => is_string($value)
            && preg_match('/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/', $value),

            default                     => false,
        };
    }

    private function isValidJson(mixed $value): bool
    {
        if (is_array($value)) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        json_decode($value);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
