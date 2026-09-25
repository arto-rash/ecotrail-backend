<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum RouteType: string implements HasLabel
{
    case Loop = 'loop';
    case OutAndBack = 'out_and_back';
    case PointToPoint = 'point_to_point';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return __("enums.route_type.{$this->value}");
    }
}
