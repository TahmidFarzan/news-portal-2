<?php

namespace App\Http\Requests;

use App\Helpers\ThemeHelper;
use App\Models\Theme;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;

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
        $theme = Theme::where('slug', $this->route('slug'))->first();

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('themes', 'name')->ignore($theme?->id),
            ],

            'options' => [
                'required',
                'array',
            ],

            'options.*' => [
                'required',
                'array',
            ],

            'options.*.valueType' => [
                'required',
                'string',
                Rule::in($this->allowedValueTypes()),
            ],

            'options.*.value' => [
                'nullable',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => __('form-requests.theme.name.required'),
            'name.string' => __('form-requests.theme.name.string'),
            'name.max' => __('form-requests.theme.name.max'),
            'name.unique' => __('form-requests.theme.name.unique'),

            'options.required' => __('form-requests.theme.options.required'),
            'options.array' => __('form-requests.theme.options.array'),

            'options.*.required' => __('form-requests.theme.options.option.required'),
            'options.*.array' => __('form-requests.theme.options.option.array'),

            'options.*.valueType.required' => __('form-requests.theme.options.value_type.required'),
            'options.*.valueType.string' => __('form-requests.theme.options.value_type.string'),
            'options.*.valueType.in' => __('form-requests.theme.options.value_type.invalid'),

            'options.*.value.required' => __('form-requests.theme.options.value.required'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $options = $this->input('options', []);

            if (! is_array($options)) {
                return;
            }

            foreach ($options as $key => $option) {
                if (! is_array($option)) {
                    continue;
                }

                $valueType = $option['valueType'] ?? null;
                $value = $option['value'] ?? null;

                if (! $valueType) {
                    continue;
                }

                if (! in_array($valueType, $this->allowedValueTypes(), true)) {
                    continue;
                }

                if (
                    $value === null ||
                    $value === ''
                ) {
                    continue;
                }

                if (! $this->isValidThemeValue($valueType, $value)) {
                    $validator->errors()->add(
                        "options.{$key}.value",
                        __('form-requests.theme.options.value.invalid_type')
                    );
                }
            }
        });
    }

    private function allowedValueTypes(): array
    {
        return [
            ThemeHelper::OPTION_VALUE_TYPE_TEXT,
            ThemeHelper::OPTION_VALUE_TYPE_STRING,
            ThemeHelper::OPTION_VALUE_TYPE_BOOLEAN,
            ThemeHelper::OPTION_VALUE_TYPE_INTEGER,
            ThemeHelper::OPTION_VALUE_TYPE_FLOAT,
            ThemeHelper::OPTION_VALUE_TYPE_DECIMAL,
            ThemeHelper::OPTION_VALUE_TYPE_JSON,
            ThemeHelper::OPTION_VALUE_TYPE_ARRAY,
            ThemeHelper::OPTION_VALUE_TYPE_URL,
            ThemeHelper::OPTION_VALUE_TYPE_IMAGE,
            ThemeHelper::OPTION_VALUE_TYPE_COLOR,
        ];
    }

    private function isValidThemeValue(string $type, mixed $value): bool
    {
        return match ($type) {
            ThemeHelper::OPTION_VALUE_TYPE_TEXT,
            ThemeHelper::OPTION_VALUE_TYPE_STRING => is_string($value),

            ThemeHelper::OPTION_VALUE_TYPE_BOOLEAN => is_bool($value)
                || filter_var(
                    $value,
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                ) !== null,

            ThemeHelper::OPTION_VALUE_TYPE_INTEGER => is_int($value)
                || filter_var($value, FILTER_VALIDATE_INT) !== false,

            ThemeHelper::OPTION_VALUE_TYPE_FLOAT => is_float($value)
                || filter_var($value, FILTER_VALIDATE_FLOAT) !== false,

            ThemeHelper::OPTION_VALUE_TYPE_DECIMAL => is_numeric($value)
                && preg_match(
                    '/^-?\d+(\.\d+)?$/',
                    (string) $value
                ),

            ThemeHelper::OPTION_VALUE_TYPE_JSON => $this->isValidJson($value),

            ThemeHelper::OPTION_VALUE_TYPE_ARRAY => is_array($value),

            ThemeHelper::OPTION_VALUE_TYPE_URL => is_string($value)
                && filter_var($value, FILTER_VALIDATE_URL) !== false,

            ThemeHelper::OPTION_VALUE_TYPE_IMAGE => $value instanceof UploadedFile
                && str_starts_with($value->getMimeType(), 'image/'),

            ThemeHelper::OPTION_VALUE_TYPE_COLOR => is_string($value)
                && preg_match(
                    '/^#([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6}|[A-Fa-f0-9]{8})$/',
                    $value
                ),

            default => false,
        };
    }

    private function isValidJson(mixed $value): bool
    {
        if (is_array($value) || is_object($value)) {
            return true;
        }

        if (! is_string($value)) {
            return false;
        }

        json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE;
    }
}
