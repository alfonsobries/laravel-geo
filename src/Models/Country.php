<?php

namespace AlfonsoBries\Geo\Models;

use AlfonsoBries\Geo\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $guarded = [];

    /**
     * @return BelongsTo<Continent, $this>
     */
    public function continent(): BelongsTo
    {
        return $this->belongsTo(Continent::class);
    }

    /**
     * @return HasMany<Division, $this>
     */
    public function divisions(): HasMany
    {
        return $this->hasMany(Division::class);
    }

    /**
     * @return HasMany<City, $this>
     */
    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    protected static function newFactory(): \AlfonsoBries\Geo\Database\Factories\CountryFactory
    {
        return \AlfonsoBries\Geo\Database\Factories\CountryFactory::new();
    }
}
