<?php
namespace App\Http\Requests;

use App\Models\Category;
use App\Models\Language;
use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;

class LocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules()
    {
        return [
            "name"             => ["required", "string", "max:200"],
            "language_id"      => ["required", "integer"],
            "category_id"      => ["nullable", "integer"],

            "brief"            => ["nullable"],
            "parent_id"        => ["nullable", "required_if:has_parent,true", "integer"],

            'latitude'         => ['nullable', 'numeric', 'between:-90,90'],
            'longitude'        => ['nullable', 'numeric', 'between:-180,180'],

            'boundary_geojson' => ['nullable'],
            'boundary_north'   => ['nullable', 'numeric', 'between:-90,90'],
            'boundary_south'   => ['nullable', 'numeric', 'between:-90,90'],
            'boundary_east'    => ['nullable', 'numeric', 'between:-180,180'],
            'boundary_west'    => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages()
    {
        return [
            'name.required'           => __('form-requests.location.name.required'),
            'name.string'             => __('form-requests.location.name.string'),
            'name.max'                => __('form-requests.location.name.max'),

            'language_id.required'    => __('form-requests.location.language_id.required'),
            'language_id.exists'      => __('form-requests.location.language_id.exists'),

            'parent_id.required_if'   => __('form-requests.location.parent_id.required'),
            'parent_id.exists'        => __('form-requests.location.parent_id.exists'),

            'category_id.required_if' => __('form-requests.location.category_id.required'),
            'category_id.exists'      => __('form-requests.location.category_id.exists'),

            'latitude.numeric'        => __('form-requests.location.latitude.numeric'),
            'latitude.between'        => __('form-requests.location.latitude.between'),

            'longitude.numeric'       => __('form-requests.location.longitude.numeric'),
            'longitude.between'       => __('form-requests.location.longitude.between'),

            'boundary_north.numeric'  => __('form-requests.location.boundary_north.numeric'),
            'boundary_north.between'  => __('form-requests.location.boundary_north.between'),

            'boundary_south.numeric'  => __('form-requests.location.boundary_south.numeric'),
            'boundary_south.between'  => __('form-requests.location.boundary_south.between'),

            'boundary_east.numeric'   => __('form-requests.location.boundary_east.numeric'),
            'boundary_east.between'   => __('form-requests.location.boundary_east.between'),

            'boundary_west.numeric'   => __('form-requests.location.boundary_west.numeric'),
            'boundary_west.between'   => __('form-requests.location.boundary_west.between'),
        ];
    }

    public function withValidator($validator)
    {
        $location = Location::where('slug', $this->route('slug'))->first();

        $validator->after(function ($validator) use ($location) {
            $data = $validator->getData();

            if (! empty($data["has_parent"])) {
                if (empty($data["parent_id"])) {
                    $validator->errors()->add(
                        'parent_id',
                        __("form-requests.location.parent_id.required")
                    );
                } else {
                    $parentQuery = Location::where("id", $data["parent_id"]);

                    if ($location) {
                        $parentQuery->where("id", "!=", $location->id);
                    }

                    if ($parentQuery->count() === 0) {
                        $validator->errors()->add(
                            'parent_id',
                            __("form-requests.location.parent_id.not_found")
                        );
                    }
                }
            }

            if (! empty($data["language_id"])) {
                $languageQuery = Language::where("id", $data["language_id"]);

                if ($languageQuery->count() === 0) {
                    $validator->errors()->add(
                        'language_id',
                        __("form-requests.location.language_id.not_found")
                    );
                }
            }

            if (! empty($data["category_id"])) {
                $categoryQuery = Category::where("id", $data["category_id"]);

                if ($categoryQuery->count() === 0) {
                    $validator->errors()->add(
                        'category_id',
                        __("form-requests.location.category_id.not_found")
                    );
                }
            }

            if (! empty($data["name"])) {
                $sameQuery = Location::where("name", $data["name"]);

                if ($location) {
                    $sameQuery->where("id", "!=", $location->id);
                }

                if (! empty($data["has_parent"])) {
                    $sameQuery->where("parent_id", $data["parent_id"]);
                } else {
                    $sameQuery->whereNull("parent_id");
                }

                if ($sameQuery->count() > 0) {
                    $validator->errors()->add(
                        'name',
                        __("form-requests.location.name.unique")
                    );
                }
            }
        });
    }
}
