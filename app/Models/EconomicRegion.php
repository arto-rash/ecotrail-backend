<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['slug', 'name', 'sort', 'hiking_advised'])]
class EconomicRegion extends Model
{
    use HasLocalizedText;

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return ['hiking_advised' => 'boolean'];
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function trails(): HasMany
    {
        return $this->hasMany(Trail::class);
    }

    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }
}
