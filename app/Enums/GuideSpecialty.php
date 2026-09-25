<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum GuideSpecialty: string implements HasLabel
{
    case MultiDay = 'multi_day';
    case Summits = 'summits';
    case DayHikes = 'day_hikes';
    case Families = 'families';
    case Photography = 'photography';
    case Culture = 'culture';
    case Birdwatching = 'birdwatching';
    case Winter = 'winter';
    case Camping = 'camping';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return __("enums.guide_specialty.{$this->value}");
    }

    /** @return array<string, string> value => label, for form options */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
