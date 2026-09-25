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
            'description' => $this->description,
            'gallery' => $this->gallery ?? [],
            'best_months' => $this->best_months ?? [],
            'waypoints' => $this->waypoints ?? [],
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
