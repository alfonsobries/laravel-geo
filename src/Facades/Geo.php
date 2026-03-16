<?php

namespace AlfonsoBries\Geo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \AlfonsoBries\Geo\GeoLocation locate(?string $ip = null)
 * @method static \AlfonsoBries\Geo\Models\Country|null country(?string $ip = null)
 * @method static string|null countryCode(?string $ip = null)
 *
 * @see \AlfonsoBries\Geo\GeoLocator
 */
class Geo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'geo';
    }
}
