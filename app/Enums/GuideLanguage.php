<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

/** Languages a guide works in, stored as ISO 639-1 codes. */
enum GuideLanguage: string implements HasLabel
{
    case Azerbaijani = 'az';
    case English = 'en';
    case Russian = 'ru';
    case Turkish = 'tr';
    case German = 'de';
    case French = 'fr';
    case Italian = 'it';
    case Spanish = 'es';
    case Arabic = 'ar';
    case Persian = 'fa';
    case Georgian = 'ka';
    case Chinese = 'zh';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return __("enums.guide_language.{$this->value}");
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
