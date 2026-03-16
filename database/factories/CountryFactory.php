<?php

namespace AlfonsoBries\Geo\Database\Factories;

use AlfonsoBries\Geo\Models\Continent;
use AlfonsoBries\Geo\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Country>
 */
class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('??'),
            'iso' => fake()->unique()->lexify('???'),
            'iso_numeric' => fake()->unique()->numerify('###'),
            'name' => fake()->unique()->country(),
            'name_official' => fake()->country(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'timezone_id' => fake()->timezone(),
            'continent_id' => Continent::factory(),
            'capital' => fake()->city(),
            'currency_code' => fake()->currencyCode(),
            'currency_name' => fake()->word(),
            'population' => fake()->numberBetween(100000, 1500000000),
            'geoname_id' => fake()->unique()->numberBetween(1000000, 9999999),
        ];
    }
}
