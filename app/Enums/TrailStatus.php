<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum TrailStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case Published = 'published';
    case Rejected = 'rejected';

    public function getLabel(): string
    {
        return ucfirst($this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Published => 'success',
            self::Rejected => 'danger',
        };
    }
}
