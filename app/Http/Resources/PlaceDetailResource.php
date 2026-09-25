<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \App\Models\Place */
class PlaceDetailResource extends PlaceResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description' => $this->localized('description'),
            'sources' => $this->sources ?? [],
            'osm_id' => $this->osm_id,
            'wikidata_id' => $this->wikidata_id,
            'trails' => TrailResource::collection($this->whenLoaded('trails')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
