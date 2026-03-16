<?php

namespace AlfonsoBries\Geo;

use AlfonsoBries\Geo\Models\City;
use AlfonsoBries\Geo\Models\Country;
use AlfonsoBries\Geo\Models\Division;

class GeoLocation
{
    private ?Country $countryModel = null;

    private ?Division $divisionModel = null;

    private ?City $cityModel = null;

    private bool $countryResolved = false;

    private bool $divisionResolved = false;

    private bool $cityResolved = false;

    public function __construct(
        public readonly ?string $countryCode = null,
        public readonly ?string $divisionName = null,
        public readonly ?string $divisionCode = null,
        public readonly ?string $cityName = null,
        public readonly ?float $latitude = null,
        public readonly ?float $longitude = null,
        public readonly ?string $timezone = null,
    ) {}

    public function country(): ?Country
    {
        if ($this->countryResolved) {
            return $this->countryModel;
        }

        $this->countryResolved = true;

        if (! $this->countryCode) {
            return null;
        }

        $this->countryModel = Country::where('code', $this->countryCode)->first();

        return $this->countryModel;
    }

    public function division(): ?Division
    {
        if ($this->divisionResolved) {
            return $this->divisionModel;
        }

        $this->divisionResolved = true;

        $country = $this->country();

        if (! $country) {
            return null;
        }

        if ($this->divisionCode) {
            $this->divisionModel = $country->divisions()
                ->where(function ($query) {
                    $query->where('code', $this->countryCode.'.'.$this->divisionCode)
                        ->orWhere('name', $this->divisionName);
                })
                ->first();
        }

        if (! $this->divisionModel && $this->divisionName) {
            $this->divisionModel = $country->divisions()
                ->where('name', $this->divisionName)
                ->first();
        }

        return $this->divisionModel;
    }

    public function city(): ?City
    {
        if ($this->cityResolved) {
            return $this->cityModel;
        }

        $this->cityResolved = true;

        $country = $this->country();

        if (! $country || ! $this->cityName) {
            return null;
        }

        $query = $country->cities()->where('name', $this->cityName);

        $division = $this->division();
        if ($division) {
            $query->where('division_id', $division->id);
        }

        $this->cityModel = $query->first();

        // Fallback: closest city by coordinates
        if (! $this->cityModel && $this->latitude && $this->longitude) {
            $this->cityModel = $country->cities()
                ->selectRaw('*, (6371 * acos(
                    cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) +
                    sin(radians(?)) * sin(radians(latitude))
                )) AS distance', [$this->latitude, $this->longitude, $this->latitude])
                ->having('distance', '<', 100)
                ->orderBy('distance')
                ->first();
        }

        return $this->cityModel;
    }
}
