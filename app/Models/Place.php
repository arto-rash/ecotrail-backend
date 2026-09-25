<?php

namespace App\Models;

use App\Enums\PlaceType;
use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'slug', 'source_key', 'type', 'name', 'summary', 'description', 'lat', 'lng', 'ele_m',
    'ele_source', 'district_id', 'economic_region_id', 'osm_id', 'wikidata_id', 'image_url',
    'image_credit', 'sources', 'is_featured',
])]
class Place extends Model
{
    use HasLocalizedText;

    public array $translatable = ['name', 'summary', 'description'];

    protected function casts(): array
    {
        return [
            'type' => PlaceType::class,
            'lat' => 'float',
            'lng' => 'float',
            'image_credit' => 'array',
            'sources' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(EconomicRegion::class, 'economic_region_id');
    }

    public function trails(): BelongsToMany
    {
        return $this->belongsToMany(Trail::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable')->where('status', 'published')->latest();
    }

    #[Scope]
    protected function withRating(Builder $query): void
    {
        $query->withAvg('reviews', 'rating')->withCount('reviews');
    }
}
