<?php

use AlfonsoBries\Geo\Models\City;
use AlfonsoBries\Geo\Models\Continent;
use AlfonsoBries\Geo\Models\Country;
use AlfonsoBries\Geo\Models\Division;
use AlfonsoBries\Geo\Models\Translations\ContinentTranslation;

test('continent has countries', function () {
    $continent = Continent::factory()->create();
    $country = Country::factory()->create(['continent_id' => $continent->id]);

    expect($continent->countries)->toHaveCount(1);
    expect($continent->countries->first()->id)->toBe($country->id);
});

test('country belongs to continent', function () {
    $country = Country::factory()->create();

    expect($country->continent)->toBeInstanceOf(Continent::class);
});

test('country has divisions and cities', function () {
    $country = Country::factory()->create();
    $division = Division::factory()->create(['country_id' => $country->id]);
    $city = City::factory()->create(['country_id' => $country->id, 'division_id' => $division->id]);

    expect($country->divisions)->toHaveCount(1);
    expect($country->cities)->toHaveCount(1);
});

test('division belongs to country and has cities', function () {
    $division = Division::factory()->create();
    $city = City::factory()->create([
        'country_id' => $division->country_id,
        'division_id' => $division->id,
    ]);

    expect($division->country)->toBeInstanceOf(Country::class);
    expect($division->cities)->toHaveCount(1);
});

test('city belongs to country and division', function () {
    $city = City::factory()->create();

    expect($city->country)->toBeInstanceOf(Country::class);
    expect($city->division)->toBeInstanceOf(Division::class);
});

test('continent has translations', function () {
    $continent = Continent::factory()->create();

    ContinentTranslation::create([
        'continent_id' => $continent->id,
        'name' => 'Europa',
        'locale' => 'es',
        'alternate_name_id' => 123456,
    ]);

    expect($continent->translations)->toHaveCount(1);
    expect($continent->getTranslation('es'))->toBe('Europa');
});
