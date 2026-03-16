<?php

namespace AlfonsoBries\Geo\Traits;

use AlfonsoBries\Geo\Models\Country;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCountry
{
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
