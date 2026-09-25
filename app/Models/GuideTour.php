<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\PriceUnit;
use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

#[Fillable([
    'guide_id', 'slug', 'title', 'summary', 'description', 'duration_value', 'duration_unit',
    'difficulty', 'price_azn', 'price_unit', 'group_size_max', 'months', 'includes',
    'meeting_point', 'sort', 'is_active',
])]
class GuideTour extends Model
{
    use HasLocalizedText;

    public array $translatable = ['title', 'summary', 'description', 'includes', 'meeting_point'];

    protected function casts(): array
    {
        return [
            'difficulty' => Difficulty::class,
            'price_unit' => PriceUnit::class,
            'months' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (GuideTour $tour) {
            if (blank($tour->slug)) {
                $title = $tour->getTranslation('title', 'en', false) ?: $tour->getTranslation('title', 'az', false);
                $tour->slug = Str::slug(Str::ascii($title)) ?: Str::lower(Str::random(8));
            }
        });
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    public function trails(): BelongsToMany
    {
        return $this->belongsToMany(Trail::class);
    }

    /** "Includes" is entered one item per line. */
    public function includesList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $this->localized('includes')))));
    }
}
