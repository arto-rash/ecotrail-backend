<?php

namespace App\Enums;

enum RouteType: string
{
    case Loop = 'loop';
    case OutAndBack = 'out_and_back';
    case PointToPoint = 'point_to_point';

    public function label(): string
    {
        return match ($this) {
            self::Loop => 'Loop',
            self::OutAndBack => 'Out & back',
            self::PointToPoint => 'Point to point',
        };
    }
}
