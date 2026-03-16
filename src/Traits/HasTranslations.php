<?php

namespace AlfonsoBries\Geo\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTranslations
{
    public function translations(): HasMany
    {
        return $this->hasMany($this->getTranslationModelClass());
    }

    public function getTranslationModelClass(): string
    {
        return str_replace('Models\\', 'Models\\Translations\\', static::class).'Translation';
    }

    public function getTranslation(string $locale, string $attribute = 'name'): ?string
    {
        return $this->translations
            ->where('locale', $locale)
            ->sortByDesc('is_preferred')
            ->first()
            ?->{$attribute};
    }
}
