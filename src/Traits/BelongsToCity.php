<?php

namespace AlfonsoBries\Geo\Traits;

use AlfonsoBries\Geo\Models\City;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCity
{
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function scopeWhereCity(Builder $query, City|string $city): Builder
    {
        if ($city instanceof City) {
            return $query->where('city_id', $city->id);
        }

        return $query->whereHas('city', fn (Builder $q) => $q->where('name', $city));
    }
}
