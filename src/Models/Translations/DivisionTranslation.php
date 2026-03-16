<?php

namespace AlfonsoBries\Geo\Models\Translations;

use AlfonsoBries\Geo\Models\Division;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DivisionTranslation extends Model
{
    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_preferred' => 'boolean',
            'is_short' => 'boolean',
            'is_colloquial' => 'boolean',
            'is_historic' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }
}
