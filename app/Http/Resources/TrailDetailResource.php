<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \App\Models\Trail */
class TrailDetailResource extends TrailResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'description' => $this->localized('description'),
            'difficulty_basis' => $this->difficulty_basis,
            'sac_scale' => $this->sac_scale,
            'descent_m' => $this->descent_m,
            'min_ele_m' => $this->min_ele_m,
            'route' => $this->route,
            'elevation_profile' => $this->elevation_profile ?? [],
            'waypoints' => $this->waypoints ?? [],
            'gallery' => $this->gallery ?? [],
            'sources' => $this->sources ?? [],
            'osm_relation_id' => $this->osm_relation_id,
            'wikidata_id' => $this->wikidata_id,
            'places' => PlaceResource::collection($this->whenLoaded('places')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
