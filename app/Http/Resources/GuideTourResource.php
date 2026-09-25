<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\GuideTour */
class GuideTourResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'title' => $this->localized('title', true),
            'summary' => $this->localized('summary'),
            'description' => $this->localized('description'),
            'duration_value' => $this->duration_value,
            'duration_unit' => $this->duration_unit,
            'difficulty' => $this->difficulty,
            'price_azn' => $this->price_azn,
            'price_unit' => $this->price_unit ? ['value' => $this->price_unit->value, 'label' => $this->price_unit->label()] : null,
            'group_size_max' => $this->group_size_max,
            'months' => array_values(array_map('intval', $this->months ?? [])),
            'includes' => $this->includesList(),
            'meeting_point' => $this->localized('meeting_point'),
            'trails' => TrailResource::collection($this->whenLoaded('trails')),
        ];
    }
}
