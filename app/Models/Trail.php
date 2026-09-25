<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\RouteType;
use App\Enums\TrailStatus;
use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'slug', 'source', 'source_key', 'name', 'summary', 'description', 'district_id',
    'economic_region_id', 'village', 'difficulty', 'difficulty_basis', 'sac_scale', 'route_type',
    'distance_km', 'segments', 'duration_minutes', 'ascent_m', 'descent_m', 'min_ele_m',
    'max_ele_m', 'stats_estimated', 'lat', 'lng', 'route', 'elevation_profile', 'network',
    'osm_relation_id', 'wikidata_id', 'image_url', 'image_credit', 'gallery', 'features',
    'waypoints', 'sources', 'status', 'is_featured', 'submitter_name', 'submitter_email',
])]
class Trail extends Model
{
    use HasLocalizedText;

    public array $translatable = ['name', 'summary', 'description'];

    protected function casts(): array
    {
        return [
            'difficulty' => Difficulty::class,
            'route_type' => RouteType::class,
            'status' => TrailStatus::class,
            'distance_km' => 'float',
            'lat' => 'float',
            'lng' => 'float',
            'route' => 'array',
            'elevation_profile' => 'array',
            'image_credit' => 'array',
            'gallery' => 'array',
            'features' => 'array',
            'waypoints' => 'array',
            'sources' => 'array',
            'stats_estimated' => 'boolean',
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

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(Place::class);
    }

    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable')->where('status', 'published')->latest();
    }

    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('status', TrailStatus::Published);
    }

    #[Scope]
    protected function withRating(Builder $query): void
    {
        $query->withAvg('reviews', 'rating')->withCount('reviews');
    }
}
