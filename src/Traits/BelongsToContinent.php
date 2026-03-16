<?php

namespace AlfonsoBries\Geo\Traits;

use AlfonsoBries\Geo\Models\Continent;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToContinent
{
    public function continent(): BelongsTo
    {
        return $this->belongsTo(Continent::class);
    }
}
