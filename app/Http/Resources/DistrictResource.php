<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\District */
class DistrictResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->localized('name', true),
            'region' => $this->whenLoaded('region', fn () => $this->region->slug),
        ];
    }
}
