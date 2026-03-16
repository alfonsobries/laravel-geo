<?php

use AlfonsoBries\Geo\Models\City;
use AlfonsoBries\Geo\Models\Continent;
use AlfonsoBries\Geo\Models\Country;
use AlfonsoBries\Geo\Models\Division;

test('Country::findByCode returns country', function () {
    $country = Country::factory()->create(['code' => 'MX']);

    expect(Country::findByCode('MX'))->not->toBeNull();
    expect(Country::findByCode('MX')->id)->toBe($country->id);
    expect(Country::findByCode('XX'))->toBeNull();
});

test('Country::findByIso returns country', function () {
    Country::factory()->create(['iso' => 'MEX']);

    expect(Country::findByIso('MEX'))->not->toBeNull();
    expect(Country::findByIso('XXX'))->toBeNull();
});

test('Continent::findByCode returns continent', function () {
    Continent::factory()->create(['code' => 'NA']);

    expect(Continent::findByCode('NA'))->not->toBeNull();
    expect(Continent::findByCode('XX'))->toBeNull();
});

test('Division::findByName returns division', function () {
    Division::factory()->create(['name' => 'Jalisco']);

    expect(Division::findByName('Jalisco'))->not->toBeNull();
    expect(Division::findByName('Nope'))->toBeNull();
});

test('City::findByName returns city', function () {
    City::factory()->create(['name' => 'Guadalajara']);

    expect(City::findByName('Guadalajara'))->not->toBeNull();
    expect(City::findByName('Nope'))->toBeNull();
});
