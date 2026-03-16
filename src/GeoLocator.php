<?php

namespace AlfonsoBries\Geo;

use AlfonsoBries\Geo\Models\City;
use AlfonsoBries\Geo\Models\Country;
use AlfonsoBries\Geo\Models\Division;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Cache;

class GeoLocator
{
    private ?Reader $reader = null;

    public function locate(?string $ip = null): GeoLocation
    {
        $ip = $ip ?? request()->ip();

        $cacheKey = 'geo:locate:'.$ip;
        $ttl = config('geo.cache.ttl', 3600);

        if (config('geo.cache.enabled', true)) {
            $cached = Cache::get($cacheKey);

            if ($cached) {
                return $cached;
            }
        }

        $location = $this->resolve($ip);

        if (config('geo.cache.enabled', true)) {
            Cache::put($cacheKey, $location, $ttl);
        }

        return $location;
    }

    public function country(?string $ip = null): ?Country
    {
        return $this->locate($ip)->country();
    }

    public function countryCode(?string $ip = null): ?string
    {
        return $this->locate($ip)->countryCode;
    }

    private function resolve(string $ip): GeoLocation
    {
        $reader = $this->getReader();

        if (! $reader) {
            return new GeoLocation;
        }

        try {
            $record = $reader->city($ip);

            return new GeoLocation(
                countryCode: $record->country->isoCode,
                divisionName: $record->mostSpecificSubdivision->name,
                divisionCode: $record->mostSpecificSubdivision->isoCode,
                cityName: $record->city->name,
                latitude: $record->location->latitude,
                longitude: $record->location->longitude,
                timezone: $record->location->timeZone,
            );
        } catch (AddressNotFoundException) {
            return new GeoLocation;
        }
    }

    private function getReader(): ?Reader
    {
        if ($this->reader) {
            return $this->reader;
        }

        $path = config('geo.maxmind.database_path');

        if (! $path || ! file_exists($path)) {
            return null;
        }

        $this->reader = new Reader($path);

        return $this->reader;
    }
}
