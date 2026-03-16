<?php

namespace AlfonsoBries\Geo\Traits;

use AlfonsoBries\Geo\Models\Division;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToDivision
{
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function scopeWhereDivision(Builder $query, Division|string $division): Builder
    {
        if ($division instanceof Division) {
            return $query->where('division_id', $division->id);
        }

        return $query->whereHas('division', fn (Builder $q) => $q->where('name', $division));
    }
}
