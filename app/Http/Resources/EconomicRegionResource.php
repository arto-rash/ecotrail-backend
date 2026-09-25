<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\EconomicRegion */
class EconomicRegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->localized('name', true),
            'hiking_advised' => $this->hiking_advised,
            'trails_count' => $this->whenCounted('trails'),
            'places_count' => $this->whenCounted('places'),
        ];
    }
}
