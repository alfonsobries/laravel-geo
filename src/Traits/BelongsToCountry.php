<?php

namespace AlfonsoBries\Geo\Traits;

use AlfonsoBries\Geo\Models\Country;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCountry
{
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function scopeWhereCountry(Builder $query, Country|string $country): Builder
    {
        if ($country instanceof Country) {
            return $query->where('country_id', $country->id);
        }

        return $query->whereHas('country', fn (Builder $q) => $q->where('code', $country));
    }

    /**
     * @param  array<Country|string>  $countries
     */
    public function scopeWhereCountries(Builder $query, array $countries): Builder
    {
        $ids = [];
        $codes = [];

        foreach ($countries as $country) {
            if ($country instanceof Country) {
                $ids[] = $country->id;
            } else {
                $codes[] = $country;
            }
        }

        return $query->where(function (Builder $q) use ($ids, $codes) {
            if (! empty($ids)) {
                $q->whereIn('country_id', $ids);
            }
            if (! empty($codes)) {
                $q->orWhereHas('country', fn (Builder $q) => $q->whereIn('code', $codes));
            }
        });
    }
}
