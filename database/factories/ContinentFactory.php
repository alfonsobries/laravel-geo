<?php

namespace AlfonsoBries\Geo\Database\Factories;

use AlfonsoBries\Geo\Models\Continent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Continent>
 */
class ContinentFactory extends Factory
{
    protected $model = Continent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('??'),
            'name' => fake()->unique()->word(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'timezone_id' => fake()->timezone(),
            'population' => fake()->numberBetween(100000, 5000000000),
            'dem' => fake()->numberBetween(0, 500),
            'feature_code' => 'CONT',
            'geoname_id' => fake()->unique()->numberBetween(1000000, 9999999),
        ];
    }
}
