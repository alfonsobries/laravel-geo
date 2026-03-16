<?php

namespace AlfonsoBries\Geo\Models\Translations;

use AlfonsoBries\Geo\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CountryTranslation extends Model
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
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
