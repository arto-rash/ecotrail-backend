<?php

namespace App\Models\Concerns;

use Spatie\Translatable\HasTranslations;

/**
 * Translatable columns stored as {"az": "...", "en": "..."}. Names fall back to the other
 * language (a proper noun beats nothing); prose does not, so an English page never shows
 * an Azerbaijani paragraph.
 */
trait HasLocalizedText
{
    use HasTranslations;

    public function localized(string $attribute, bool $fallback = false): ?string
    {
        $value = $this->getTranslation($attribute, app()->getLocale(), false);
        if (($value === '' || $value === null) && $fallback) {
            $translations = $this->getTranslations($attribute);
            $value = $translations['az'] ?? $translations['en'] ?? reset($translations) ?: null;
        }

        return $value === '' ? null : $value;
    }
}
