<?php

namespace App\Filament\Support;

use Closure;
use Filament\Schemas\Components\Grid;
use Illuminate\Database\Eloquent\Model;

/**
 * Side-by-side AZ/EN inputs for spatie-translatable attributes. Form state holds the
 * full {"az": ..., "en": ...} array, which the model saves via setTranslations.
 */
class Translatable
{
    public const LOCALES = ['az' => 'Azərbaycanca', 'en' => 'English'];

    /**
     * @param  Closure(string $statePath, string $label): \Filament\Forms\Components\Field  $field
     * @param  bool  $requireOne  at least one of the two languages must be filled
     */
    public static function field(string $name, string $label, Closure $field, bool $requireOne = false): Grid
    {
        return Grid::make(2)->schema(collect(self::LOCALES)->map(function ($langLabel, $locale) use ($name, $label, $field, $requireOne) {
            $other = $locale === 'az' ? 'en' : 'az';
            $input = $field("{$name}.{$locale}", "{$label} ({$langLabel})");

            return $requireOne ? $input->requiredWithout("{$name}.{$other}") : $input;
        })->values()->all())->columnSpanFull();
    }

    /** Replace localized strings in form data with each attribute's full translation array. */
    public static function fill(Model $record, array $data): array
    {
        foreach ($record->translatable ?? [] as $attribute) {
            $data[$attribute] = $record->getTranslations($attribute) + array_fill_keys(array_keys(self::LOCALES), null);
        }

        return $data;
    }

    /** Drop empty languages so a missing translation falls back instead of saving null. */
    public static function clean(array $data, array $attributes): array
    {
        foreach ($attributes as $attribute) {
            if (is_array($data[$attribute] ?? null)) {
                $data[$attribute] = array_filter($data[$attribute], fn ($v) => filled($v));
            }
        }

        return $data;
    }

    /**
     * Update a record, replacing (not merging) its translations so a cleared language
     * is actually removed.
     */
    public static function save(Model $record, array $data): Model
    {
        foreach ($record->translatable ?? [] as $attribute) {
            if (array_key_exists($attribute, $data)) {
                $record->replaceTranslations($attribute, array_filter((array) $data[$attribute], fn ($v) => filled($v)));
                unset($data[$attribute]);
            }
        }
        $record->fill($data)->save();

        return $record;
    }
}
