<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PlaceType: string implements HasLabel
{
    case Waterfall = 'waterfall';
    case Lake = 'lake';
    case Peak = 'peak';
    case Village = 'village';
    case Historic = 'historic';
    case Park = 'park';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return __("enums.place_type.{$this->value}");
    }
}
