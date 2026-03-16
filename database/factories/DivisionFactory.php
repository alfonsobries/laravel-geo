<?php

namespace AlfonsoBries\Geo\Database\Factories;

use AlfonsoBries\Geo\Models\Country;
use AlfonsoBries\Geo\Models\Division;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Division>
 */
class DivisionFactory extends Factory
{
    protected $model = Division::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->state(),
            'country_id' => Country::factory(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'timezone_id' => fake()->timezone(),
            'population' => fake()->numberBetween(10000, 50000000),
            'geoname_id' => fake()->unique()->numberBetween(1000000, 9999999),
        ];
    }
}
