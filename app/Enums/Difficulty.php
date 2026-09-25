<?php

namespace App\Enums;

enum Difficulty: string
{
    case Easy = 'easy';
    case Moderate = 'moderate';
    case Hard = 'hard';
    case Expert = 'expert';

    public function label(): string
    {
        return __("enums.difficulty.{$this->value}");
    }
}
