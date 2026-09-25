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
            'slug' => $this->slug,
            'name' => $this->name,
            'kind' => $this->kind,
            'headline' => $this->localized('headline', true),
            'bio' => $this->localized('bio', true),
            'languages' => $this->languages ?? [],
            'regions' => $this->regions ?? [],
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,
            'is_verified' => $this->is_verified,
        ];
    }
}
