<?php

namespace AlfonsoBries\Geo\Traits;

use AlfonsoBries\Geo\Models\Division;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToDivision
{
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
