<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Place */
class PlaceResource extends JsonResource
{
    use Concerns;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'type' => $this->type,
            'name' => $this->localized('name', true),
            'summary' => $this->localized('summary'),
            ...$this->placeRefs(),
            'location' => ['lat' => $this->lat, 'lng' => $this->lng],
            'ele_m' => $this->ele_m,
            'ele_source' => $this->ele_source,
            'image' => $this->image(),
            'is_featured' => $this->is_featured,
            ...$this->rating(),
        ];
    }
}
