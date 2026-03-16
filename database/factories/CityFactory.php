<?php

namespace AlfonsoBries\Geo\Database\Factories;

use AlfonsoBries\Geo\Models\City;
use AlfonsoBries\Geo\Models\Country;
use AlfonsoBries\Geo\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->city(),
            'country_id' => Country::factory(),
            'division_id' => Division::factory(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'timezone_id' => fake()->timezone(),
            'population' => fake()->numberBetween(1000, 15000000),
            'geoname_id' => fake()->unique()->numberBetween(1000000, 9999999),
        ];
    }
}
