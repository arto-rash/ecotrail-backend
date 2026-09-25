<?php

namespace App\Http\Resources;

/** Shared shapes for trail and place resources. */
trait Concerns
{
    protected function placeRefs(): array
    {
        return [
            'district' => $this->whenLoaded('district', fn () => $this->district ? [
                'slug' => $this->district->slug,
                'name' => $this->district->localized('name', true),
            ] : null),
            'region' => $this->whenLoaded('region', fn () => $this->region ? [
                'slug' => $this->region->slug,
                'name' => $this->region->localized('name', true),
            ] : null),
        ];
    }

    protected function image(): ?array
    {
        if (! $this->image_url) {
            return null;
        }
        $credit = $this->image_credit ?? [];
        $caption = $credit['caption'] ?? null;

        return [
            'url' => $this->image_url,
            'author' => $credit['author'] ?? null,
            'license' => $credit['license'] ?? null,
            'source' => $credit['source'] ?? null,
            'caption' => is_array($caption) ? ($caption[app()->getLocale()] ?? $caption['az'] ?? null) : $caption,
        ];
    }

    protected function rating(): array
    {
        return [
            'rating' => $this->when(
                isset($this->reviews_count),
                fn () => $this->reviews_count ? round((float) $this->reviews_avg_rating, 1) : null,
            ),
            'reviews_count' => $this->whenCounted('reviews'),
        ];
    }
}
