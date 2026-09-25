<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Trail */
class TrailResource extends JsonResource
{
    use Concerns;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'source' => $this->source,
            'name' => $this->localized('name', true),
            'summary' => $this->localized('summary'),
            ...$this->placeRefs(),
            'village' => $this->village,
            'difficulty' => $this->difficulty,
            'route_type' => $this->route_type,
            'distance_km' => $this->distance_km,
            'segments' => $this->segments,
            'duration_minutes' => $this->duration_minutes,
            'ascent_m' => $this->ascent_m,
            'max_ele_m' => $this->max_ele_m,
            'stats_estimated' => $this->stats_estimated,
            'location' => ['lat' => $this->lat, 'lng' => $this->lng],
            'image' => $this->image(),
            'features' => $this->features ?? [],
            'network' => $this->network,
            'is_featured' => $this->is_featured,
            ...$this->rating(),
        ];
    }
}
