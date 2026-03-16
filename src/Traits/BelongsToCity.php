<?php

namespace AlfonsoBries\Geo\Traits;

use AlfonsoBries\Geo\Models\City;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCity
{
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
