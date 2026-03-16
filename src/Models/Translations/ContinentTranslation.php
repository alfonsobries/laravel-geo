<?php

namespace AlfonsoBries\Geo\Models\Translations;

use AlfonsoBries\Geo\Models\Continent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContinentTranslation extends Model
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
     * @return BelongsTo<Continent, $this>
     */
    public function continent(): BelongsTo
    {
        return $this->belongsTo(Continent::class);
    }
}
