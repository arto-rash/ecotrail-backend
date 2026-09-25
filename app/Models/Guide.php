<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'slug', 'name', 'kind', 'headline', 'bio', 'languages', 'specialties',
    'region_id', 'rating', 'trips_count', 'price_from_azn', 'is_verified',
])]
class Guide extends Model
{
    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'specialties' => 'array',
            'rating' => 'float',
            'is_verified' => 'boolean',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}
