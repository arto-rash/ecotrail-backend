<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

/** @mixin \App\Models\Guide */
class GuideDetailResource extends GuideResource
{
    public function toArray(Request $request): array
    {
        return [
            ...parent::toArray($request),
            'bio' => $this->localized('bio', true),
            'certifications' => collect($this->certifications ?? [])
                ->map(fn ($c) => ['name' => $c['name'] ?? '', 'issuer' => $c['issuer'] ?? null, 'year' => isset($c['year']) ? (int) $c['year'] : null])
                ->filter(fn ($c) => $c['name'] !== '')->values(),
            'tours' => GuideTourResource::collection($this->whenLoaded('activeTours')),
            'reviews' => ReviewResource::collection($this->whenLoaded('reviews')),
        ];
    }
}
