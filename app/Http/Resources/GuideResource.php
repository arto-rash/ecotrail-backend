<?php

namespace App\Http\Resources;

use App\Enums\GuideLanguage;
use App\Enums\GuideSpecialty;
use App\Models\EconomicRegion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A guide as listed publicly. Contact details are never included here; only which
 * channels exist. Values come from POST /guides/{slug}/contact.
 *
 * @mixin \App\Models\Guide
 */
class GuideResource extends JsonResource
{
    private static ?array $regionNames = null;

    public function toArray(Request $request): array
    {
        return [
            'slug' => $this->slug,
            'name' => $this->name,
            'kind' => $this->kind,
            'photo' => $this->photoUrl(),
            'headline' => $this->localized('headline', true),
            'experience_years' => $this->experience_years,
            'specialties' => collect($this->specialties ?? [])
                ->map(fn ($v) => GuideSpecialty::tryFrom($v))->filter()
                ->map(fn (GuideSpecialty $s) => ['value' => $s->value, 'label' => $s->label()])->values(),
            'languages' => collect($this->languages ?? [])
                ->map(fn ($code) => ['code' => $code, 'label' => GuideLanguage::tryFrom($code)?->label() ?? $code])->values(),
            'regions' => collect($this->regions ?? [])
                ->map(fn ($slug) => ['slug' => $slug, 'name' => self::regionName($slug)])->values(),
            'price_from_azn' => $this->price_from_azn,
            'price_unit' => $this->price_unit ? ['value' => $this->price_unit->value, 'label' => $this->price_unit->label()] : null,
            'tours_count' => $this->whenCounted('activeTours'),
            'rating' => $this->when(isset($this->reviews_count), fn () => $this->reviews_count ? round((float) $this->reviews_avg_rating, 1) : null),
            'reviews_count' => $this->whenCounted('reviews'),
            'contact_channels' => $this->contactChannels(),
            'is_verified' => $this->is_verified,
            'is_sample' => $this->is_sample,
        ];
    }

    private static function regionName(string $slug): string
    {
        self::$regionNames ??= EconomicRegion::all()->mapWithKeys(fn ($r) => [$r->slug => $r->getTranslations('name')])->all();
        $names = self::$regionNames[$slug] ?? [];

        return $names[app()->getLocale()] ?? $names['az'] ?? $slug;
    }

    /** Reset the per-request region name cache (locale changes between requests in tests). */
    public static function flushCache(): void
    {
        self::$regionNames = null;
    }
}
