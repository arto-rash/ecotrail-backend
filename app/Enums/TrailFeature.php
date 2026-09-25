<?php

namespace App\Enums;

enum TrailFeature: string
{
    case Waterfall = 'waterfall';
    case Lake = 'lake';
    case Views = 'views';
    case Forest = 'forest';
    case Summit = 'summit';
    case Village = 'village';
    case Historic = 'historic';
    case Wildlife = 'wildlife';
    case Camping = 'camping';
    case River = 'river';
    case Canyon = 'canyon';
    case SpringWater = 'spring_water';

    public function label(): string
    {
        return match ($this) {
            self::SpringWater => 'Spring water',
            default => ucfirst($this->value),
        };
    }
}
