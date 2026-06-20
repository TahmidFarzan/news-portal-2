<?php
namespace App\Http\Requests;

use App\Helpers\ThemeHelper;
use App\Models\Theme;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;

class ThemeRequest extends FormRequest
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
            "group.required" => __("form-requests.theme.group.required"),
            "group.string"   => __("form-requests.theme.group.string"),
            "group.max"      => __("form-requests.theme.group.max"),

            "label.required" => __("form-requests.theme.label.required"),
            "label.string"   => __("form-requests.theme.label.string"),
            "label.max"      => __("form-requests.theme.label.max"),

            "type.required"  => __("form-requests.theme.type.required"),
            "type.string"    => __("form-requests.theme.type.string"),
            "type.max"       => __("form-requests.theme.type.max"),

            "value.required" => __("form-requests.theme.value.required"),
            "value.string"   => __("form-requests.theme.value.string"),
            "value.max"      => __("form-requests.theme.value.max"),
        ];
    }

    public function withValidator($validator): void
    {
        $theme = Theme::where('slug', $this->route('slug'))->first();

        $isUpdate = $this->route('slug') ? true : false;

        $validator->after(function ($validator) use ($theme, $isUpdate) {
            $data = $validator->getData();

            if (! empty($data['group'])) {
                $allowedGroups = [
                    ThemeHelper::GROUP_APP,
                    ThemeHelper::GROUP_MENU,
                    ThemeHelper::GROUP_SOCIAL_LINK,
                ];

                if (! in_array($data['group'], $allowedGroups, true)) {
                    $validator->errors()->add(
                        'group',
                        __('form-requests.theme.group.not_exit')
                    );
                }
            }

            if (! empty($data['type'])) {
                $allowedTypes = [
                    ThemeHelper::VALUE_TYPE_TEXT,
                    ThemeHelper::VALUE_TYPE_STRING,
                    ThemeHelper::VALUE_TYPE_BOOLEAN,
                    ThemeHelper::VALUE_TYPE_INTEGER,
                    ThemeHelper::VALUE_TYPE_FLOAT,
                    ThemeHelper::VALUE_TYPE_DECIMAL,
                    ThemeHelper::VALUE_TYPE_JSON,
                    ThemeHelper::VALUE_TYPE_ARRAY,
                    ThemeHelper::VALUE_TYPE_URL,
                    ThemeHelper::VALUE_TYPE_IMAGE,
                    ThemeHelper::VALUE_TYPE_COLOR,
                ];

                if (! in_array($data['type'], $allowedTypes, true)) {
                    $validator->errors()->add(
                        'type',
                        __('form-requests.theme.type.not_exit')
                    );
                }
            }

            if (! empty($data["group"]) && empty($data["label"])) {
                $sameThemeFound = Theme::where("group", $data["group"])->where("label", $data["label"]);
                if ($theme && $isUpdate) {
                    $sameThemeFound = $sameThemeFound->whereNot('id', $sameThemeFound->id);
                }
                $sameThemeFound = $sameThemeFound->count();
                if ($sameThemeFound) {
                    $validator->errors()->add(
                        'group',
                        __("form-requests.theme.group.unique")
                    );

                    $validator->errors()->add(
                        'label',
                        __("form-requests.theme.label.unique")
                    );
                }
            }

            if (! empty($data["type"]) && empty($data["value"])) {
                if (! $this->isValidThemeValue($data['type'], $data['value'])) {
                    $validator->errors()->add(
                        'value',
                        __('form-requests.theme.value.invalid_type')
                    );
                }
            }
        });
    }

    private function isValidThemeValue(string $type, mixed $value): bool
    {
        return match ($type) {
            ThemeHelper::VALUE_TYPE_TEXT,
            ThemeHelper::VALUE_TYPE_STRING  => is_string($value),

            ThemeHelper::VALUE_TYPE_BOOLEAN => is_bool($value)
            || filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== null,

            ThemeHelper::VALUE_TYPE_INTEGER => is_int($value)
            || filter_var($value, FILTER_VALIDATE_INT) !== false,

            ThemeHelper::VALUE_TYPE_FLOAT   => is_float($value)
            || filter_var($value, FILTER_VALIDATE_FLOAT) !== false,

            ThemeHelper::VALUE_TYPE_DECIMAL => is_numeric($value)
            && preg_match('/^-?\d+(\.\d+)?$/', (string) $value),

            ThemeHelper::VALUE_TYPE_JSON    => $this->isValidJson($value),

            ThemeHelper::VALUE_TYPE_ARRAY   => is_array($value),

            ThemeHelper::VALUE_TYPE_URL     => is_string($value)
            && filter_var($value, FILTER_VALIDATE_URL) !== false,

            ThemeHelper::VALUE_TYPE_IMAGE   => $value instanceof UploadedFile
            && str_starts_with($value->getMimeType(), 'image/'),

            ThemeHelper::VALUE_TYPE_COLOR   => is_string($value)
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
