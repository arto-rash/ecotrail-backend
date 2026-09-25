<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['author_name', 'rating', 'body', 'locale', 'status', 'ip_hash'])]
class Review extends Model
{
    protected function casts(): array
    {
        return ['rating' => 'integer'];
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
