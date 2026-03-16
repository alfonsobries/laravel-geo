<?php

namespace AlfonsoBries\Geo\Models;

use AlfonsoBries\Geo\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class City extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $guarded = [];

    /**
     * @return BelongsTo<Country, $this>
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    /**
     * @return BelongsTo<Division, $this>
     */
    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    protected static function newFactory(): \AlfonsoBries\Geo\Database\Factories\CityFactory
    {
        return \AlfonsoBries\Geo\Database\Factories\CityFactory::new();
    }
}
