<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Guide */
class GuideResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'kind' => $this->kind,
            'headline' => $this->headline,
            'bio' => $this->bio,
            'languages' => $this->languages,
            'specialties' => $this->specialties ?? [],
            'region' => new RegionResource($this->whenLoaded('region')),
            'rating' => $this->rating,
            'trips_count' => $this->trips_count,
            'price_from_azn' => $this->price_from_azn,
            'is_verified' => $this->is_verified,
        ];
    }
}
