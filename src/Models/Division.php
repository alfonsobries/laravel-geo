<?php

namespace AlfonsoBries\Geo\Models;

use AlfonsoBries\Geo\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
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
     * @return HasMany<City, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    protected static function newFactory(): \AlfonsoBries\Geo\Database\Factories\DivisionFactory
    {
        return \AlfonsoBries\Geo\Database\Factories\DivisionFactory::new();
    }
}
