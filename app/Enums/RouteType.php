<?php

namespace App\Enums;

enum RouteType: string
{
    case Loop = 'loop';
    case OutAndBack = 'out_and_back';
    case PointToPoint = 'point_to_point';

    public function label(): string
    {
        return __("enums.route_type.{$this->value}");
    }
}
