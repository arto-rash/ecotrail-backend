<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'kind', 'email', 'phone', 'website', 'regions', 'languages', 'message', 'status'])]
class GuideApplication extends Model
{
    protected function casts(): array
    {
        return ['regions' => 'array', 'languages' => 'array'];
    }
}
