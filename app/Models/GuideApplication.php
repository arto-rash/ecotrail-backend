<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name', 'kind', 'email', 'phone', 'whatsapp', 'website', 'instagram', 'regions', 'languages',
    'experience_years', 'specialties', 'certifications', 'message', 'status', 'guide_id',
])]
class GuideApplication extends Model
{
    protected function casts(): array
    {
        return ['regions' => 'array', 'languages' => 'array', 'specialties' => 'array'];
    }

    public function guide(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }
}
