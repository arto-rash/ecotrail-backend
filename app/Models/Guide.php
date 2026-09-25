<?php

namespace App\Models;

use App\Enums\PriceUnit;
use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

#[Fillable([
    'slug', 'name', 'kind', 'photo_path', 'headline', 'bio', 'experience_years', 'certifications',
    'specialties', 'languages', 'regions', 'price_from_azn', 'price_unit', 'email', 'phone',
    'whatsapp', 'website', 'instagram', 'telegram', 'is_verified', 'is_sample', 'is_published',
])]
class Guide extends Model
{
    use HasLocalizedText;

    /** Contact fields; only ever returned by the reveal endpoint. */
    public const CONTACT_CHANNELS = ['phone', 'whatsapp', 'email', 'website', 'instagram', 'telegram'];

    public array $translatable = ['headline', 'bio'];

    protected function casts(): array
    {
        return [
            'certifications' => 'array',
            'specialties' => 'array',
            'languages' => 'array',
            'regions' => 'array',
            'price_unit' => PriceUnit::class,
            'is_verified' => 'boolean',
            'is_sample' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function tours(): HasMany
    {
        return $this->hasMany(GuideTour::class)->orderBy('sort')->orderBy('id');
    }

    public function activeTours(): HasMany
    {
        return $this->tours()->where('is_active', true);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable')->where('status', 'published')->latest();
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? '/storage/'.ltrim($this->photo_path, '/') : null;
    }

    /** @return list<string> the contact channels this guide has filled in */
    public function contactChannels(): array
    {
        return array_values(array_filter(self::CONTACT_CHANNELS, fn ($c) => filled($this->{$c})));
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('is_published', true);
    }

    #[Scope]
    protected function withRating(Builder $query): void
    {
        $query->withAvg('reviews', 'rating')->withCount('reviews');
    }
}
