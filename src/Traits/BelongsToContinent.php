<?php

namespace AlfonsoBries\Geo\Traits;

use AlfonsoBries\Geo\Models\Continent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToContinent
{
    public function continent(): BelongsTo
    {
        return $this->belongsTo(Continent::class);
    }

    public function scopeWhereContinent(Builder $query, Continent|string $continent): Builder
    {
        if ($continent instanceof Continent) {
            return $query->where('continent_id', $continent->id);
        }

        return $query->whereHas('continent', fn (Builder $q) => $q->where('code', $continent));
    }
}
