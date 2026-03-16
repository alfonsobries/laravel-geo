<?php

namespace AlfonsoBries\Geo\Models;

use AlfonsoBries\Geo\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Continent extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $guarded = [];

    public static function findByCode(string $code): ?static
    {
        return static::where('code', $code)->first();
    }

    /**
     * @return HasMany<Country, $this>
     */
    public function countries(): HasMany
    {
        return $this->hasMany(Country::class);
    }

    protected static function newFactory(): \AlfonsoBries\Geo\Database\Factories\ContinentFactory
    {
        return \AlfonsoBries\Geo\Database\Factories\ContinentFactory::new();
    }
}
