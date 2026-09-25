<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedText;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['slug', 'name', 'kind', 'headline', 'bio', 'languages', 'regions', 'email', 'phone', 'website', 'is_verified', 'is_sample'])]
class Guide extends Model
{
    use HasLocalizedText;

    public array $translatable = ['headline', 'bio'];

    protected function casts(): array
    {
        return [
            'languages' => 'array',
            'regions' => 'array',
            'is_verified' => 'boolean',
            'is_sample' => 'boolean',
        ];
    }
}
