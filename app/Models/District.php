<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['pcode', 'slug', 'name', 'type', 'economic_region_id'])]
class District extends Model
{
    use HasLocalizedText;

    public array $translatable = ['name'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(EconomicRegion::class, 'economic_region_id');
    }
}
