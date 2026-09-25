<?php

namespace App\Enums;

enum PlaceType: string
{
    case Waterfall = 'waterfall';
    case Lake = 'lake';
    case Peak = 'peak';
    case Village = 'village';
    case Historic = 'historic';
    case Park = 'park';

    public function label(): string
    {
        return __("enums.place_type.{$this->value}");
    }
}
