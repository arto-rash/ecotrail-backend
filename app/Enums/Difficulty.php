<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Difficulty: string implements HasLabel
{
    case Easy = 'easy';
    case Moderate = 'moderate';
    case Hard = 'hard';
    case Expert = 'expert';

    public function getLabel(): string
    {
        return $this->label();
    }

    public function label(): string
    {
        return __("enums.difficulty.{$this->value}");
    }
}
