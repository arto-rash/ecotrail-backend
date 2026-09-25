<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Trail */
class TrailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'name' => $this->name,
            'region' => new RegionResource($this->whenLoaded('region')),
            'village' => $this->village,
            'summary' => $this->summary,
            'difficulty' => $this->difficulty,
            'route_type' => $this->route_type,
            'distance_km' => $this->distance_km,
            'duration_minutes' => $this->duration_minutes,
            'elevation_gain_m' => $this->elevation_gain_m,
            'max_altitude_m' => $this->max_altitude_m,
            'location' => ['lat' => $this->lat, 'lng' => $this->lng],
            'cover_image' => $this->cover_image,
            'features' => $this->features ?? [],
            'offline_map' => $this->offline_map,
            'is_featured' => $this->is_featured,
            'rating' => $this->when(
                isset($this->reviews_avg_rating) || isset($this->reviews_count),
                fn () => $this->reviews_avg_rating ? round((float) $this->reviews_avg_rating, 1) : null,
            ),
            'reviews_count' => $this->whenCounted('reviews'),
        ];
    }
}
