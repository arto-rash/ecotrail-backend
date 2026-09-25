<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PriceUnit: string implements HasLabel
{
    case PerPerson = 'per_person';
    case PerGroup = 'per_group';
    case PerDay = 'per_day';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return __("enums.price_unit.{$this->value}");
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
