<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\RouteType;
use App\Enums\TrailStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug', 'name', 'region_id', 'village', 'summary', 'description',
    'difficulty', 'route_type', 'distance_km', 'duration_minutes',
    'elevation_gain_m', 'max_altitude_m', 'lat', 'lng', 'cover_image',
    'gallery', 'features', 'best_months', 'offline_map', 'waypoints',
    'status', 'is_featured', 'submitter_name', 'submitter_email',
])]
class Trail extends Model
{
    protected function casts(): array
    {
        return [
            'difficulty' => Difficulty::class,
            'route_type' => RouteType::class,
            'status' => TrailStatus::class,
            'distance_km' => 'float',
            'lat' => 'float',
            'lng' => 'float',
            'gallery' => 'array',
            'features' => 'array',
            'best_months' => 'array',
            'waypoints' => 'array',
            'offline_map' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->latest('hiked_on');
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
