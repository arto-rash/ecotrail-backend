<?php

namespace App\Http\Requests;

use App\Enums\Difficulty;
use App\Enums\RouteType;
use App\Enums\TrailFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrailRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:120'],
            'village' => ['nullable', 'string', 'max:120'],
            'description' => ['required', 'string', 'min:30', 'max:5000'],
            'difficulty' => ['required', Rule::enum(Difficulty::class)],
            'route_type' => ['required', Rule::enum(RouteType::class)],
            'distance_km' => ['required', 'numeric', 'min:0.1', 'max:500'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:10080'],
            'ascent_m' => ['nullable', 'integer', 'min:0', 'max:9000'],
            'max_ele_m' => ['nullable', 'integer', 'min:-50', 'max:5500'],
            'lat' => ['required', 'numeric', 'between:38.3,41.95'],
            'lng' => ['required', 'numeric', 'between:44.7,50.7'],
            'features' => ['nullable', 'array'],
            'features.*' => [Rule::enum(TrailFeature::class)],
            'waypoints' => ['nullable', 'array', 'max:30'],
            'waypoints.*' => ['string', 'max:120'],
            'photos' => ['nullable', 'array', 'max:10'],
            'photos.*' => ['image', 'max:8192'],
            'submitter_name' => ['required', 'string', 'max:120'],
            'submitter_email' => ['required', 'email', 'max:190'],
        ];
    }

    public function messages(): array
    {
        return [
            'lat.between' => __('The trailhead must be inside Azerbaijan.'),
            'lng.between' => __('The trailhead must be inside Azerbaijan.'),
        ];
    }
}
