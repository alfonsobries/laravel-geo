<?php

use AlfonsoBries\Geo\Sync\DumpSyncer;
use AlfonsoBries\Geo\Sync\GeoApiClient;
use AlfonsoBries\Geo\Sync\GeoSyncManager;
use AlfonsoBries\Geo\Sync\TableSyncer;
use Illuminate\Support\Facades\Http;

function createManager(): GeoSyncManager
{
    $client = new GeoApiClient;

    return new GeoSyncManager($client, new TableSyncer, new DumpSyncer($client));
}

beforeEach(function () {
    Http::fake([
        '*/api/v1/manifest' => Http::response([
            'data' => [
                'continents' => ['checksum' => 'abc123', 'record_count' => 1, 'last_synced_at' => now()->toIso8601String(), 'dump_checksum' => null],
                'continent_translations' => ['checksum' => 'def456', 'record_count' => 1, 'last_synced_at' => now()->toIso8601String(), 'dump_checksum' => null],
                'countries' => ['checksum' => 'ghi789', 'record_count' => 1, 'last_synced_at' => now()->toIso8601String(), 'dump_checksum' => null],
                'country_translations' => ['checksum' => 'jkl012', 'record_count' => 0, 'last_synced_at' => now()->toIso8601String(), 'dump_checksum' => null],
                'divisions' => ['checksum' => 'mno345', 'record_count' => 1, 'last_synced_at' => now()->toIso8601String(), 'dump_checksum' => null],
                'division_translations' => ['checksum' => 'pqr678', 'record_count' => 0, 'last_synced_at' => now()->toIso8601String(), 'dump_checksum' => null],
                'cities' => ['checksum' => 'stu901', 'record_count' => 1, 'last_synced_at' => now()->toIso8601String(), 'dump_checksum' => null],
                'city_translations' => ['checksum' => 'vwx234', 'record_count' => 0, 'last_synced_at' => now()->toIso8601String(), 'dump_checksum' => null],
            ],
        ]),

        '*/api/v1/continents*' => Http::response([
            'data' => [[
                'geoname_id' => 6255148,
                'code' => 'EU',
                'name' => 'Europe',
                'latitude' => '48.6908333',
                'longitude' => '9.1405556',
                'timezone_id' => 'Europe/Berlin',
                'population' => 741447158,
                'dem' => 151,
                'feature_code' => 'CONT',
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
                'translations' => [[
                    'alternate_name_id' => 123456,
                    'name' => 'Europa',
                    'locale' => 'es',
                    'is_preferred' => true,
                    'is_short' => false,
                    'is_colloquial' => false,
                    'is_historic' => false,
                ]],
            ]],
        ]),

        '*/api/v1/countries*' => Http::response([
            'data' => [[
                'geoname_id' => 2921044,
                'code' => 'DE',
                'iso' => 'DEU',
                'iso_numeric' => '276',
                'name' => 'Germany',
                'name_official' => 'Federal Republic of Germany',
                'latitude' => '51.5000000',
                'longitude' => '10.5000000',
                'timezone_id' => 'Europe/Berlin',
                'continent_geoname_id' => 6255148,
                'capital' => 'Berlin',
                'currency_code' => 'EUR',
                'currency_name' => 'Euro',
                'tld' => '.de',
                'phone_code' => '49',
                'postal_code_format' => '#####',
                'postal_code_regex' => null,
                'languages' => 'de',
                'neighbours' => 'AT,BE,CH,CZ,DK,FR,LU,NL,PL',
                'area' => 357021,
                'fips' => 'GM',
                'population' => 83149300,
                'elevation' => null,
                'dem' => 303,
                'feature_code' => 'PCLI',
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
                'translations' => [],
            ]],
        ]),

        '*/api/v1/divisions*' => Http::response([
            'data' => [[
                'geoname_id' => 2951839,
                'name' => 'Bavaria',
                'country_geoname_id' => 2921044,
                'latitude' => '48.7900000',
                'longitude' => '11.4900000',
                'timezone_id' => 'Europe/Berlin',
                'population' => 12843514,
                'elevation' => null,
                'dem' => 500,
                'code' => 'BY',
                'feature_code' => 'ADM1',
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
                'translations' => [],
            ]],
            'next_cursor' => null,
            'has_more' => false,
        ]),

        '*/api/v1/cities*' => Http::response([
            'data' => [[
                'geoname_id' => 2867714,
                'name' => 'Munich',
                'country_geoname_id' => 2921044,
                'division_geoname_id' => 2951839,
                'latitude' => '48.1374300',
                'longitude' => '11.5754900',
                'timezone_id' => 'Europe/Berlin',
                'population' => 1260391,
                'elevation' => 524,
                'dem' => 524,
                'feature_code' => 'PPLA',
                'created_at' => '2024-01-01T00:00:00.000000Z',
                'updated_at' => '2024-01-01T00:00:00.000000Z',
                'translations' => [],
            ]],
            'next_cursor' => null,
            'has_more' => false,
        ]),

        '*/api/v1/deletions*' => Http::response(['data' => []]),
    ]);

    config(['geo.api_url' => 'https://geo.vexilo.com', 'geo.api_key' => 'test-key']);
});

test('full sync creates all geo records', function () {
    $manager = createManager();

    $manager->sync(force: true, mode: 'incremental');

    $this->assertDatabaseHas('continents', ['geoname_id' => 6255148, 'name' => 'Europe']);
    $this->assertDatabaseHas('continent_translations', ['alternate_name_id' => 123456, 'name' => 'Europa']);
    $this->assertDatabaseHas('countries', ['geoname_id' => 2921044, 'name' => 'Germany']);
    $this->assertDatabaseHas('divisions', ['geoname_id' => 2951839, 'name' => 'Bavaria']);
    $this->assertDatabaseHas('cities', ['geoname_id' => 2867714, 'name' => 'Munich']);
});

test('sync resolves foreign keys via geoname_id', function () {
    $manager = createManager();

    $manager->sync(force: true, mode: 'incremental');

    $continent = \Illuminate\Support\Facades\DB::table('continents')->where('geoname_id', 6255148)->first();
    $country = \Illuminate\Support\Facades\DB::table('countries')->where('geoname_id', 2921044)->first();
    $division = \Illuminate\Support\Facades\DB::table('divisions')->where('geoname_id', 2951839)->first();
    $city = \Illuminate\Support\Facades\DB::table('cities')->where('geoname_id', 2867714)->first();

    expect($country->continent_id)->toBe($continent->id);
    expect($division->country_id)->toBe($country->id);
    expect($city->country_id)->toBe($country->id);
    expect($city->division_id)->toBe($division->id);
});

test('sync skips tables when checksums match', function () {
    $manager = createManager();

    $manager->sync(force: true, mode: 'incremental');

    $skipped = [];
    $manager->sync(force: false, mode: 'incremental', onProgress: function (string $table, string $status) use (&$skipped): void {
        if ($status === 'skipped') {
            $skipped[] = $table;
        }
    });

    expect($skipped)->toContain('continents', 'countries', 'divisions', 'cities');
});

test('force sync always re-syncs', function () {
    $manager = createManager();

    $manager->sync(force: true, mode: 'incremental');

    $synced = [];
    $manager->sync(force: true, mode: 'incremental', onProgress: function (string $table, string $status) use (&$synced): void {
        if ($status === 'syncing') {
            $synced[] = $table;
        }
    });

    expect($synced)->toContain('continents', 'countries', 'divisions', 'cities');
});

test('sync specific tables only', function () {
    $manager = createManager();

    $synced = [];
    $manager->sync(tables: ['continents'], force: true, mode: 'incremental', onProgress: function (string $table, string $status) use (&$synced): void {
        if ($status === 'syncing') {
            $synced[] = $table;
        }
    });

    expect($synced)->toBe(['continents']);
    $this->assertDatabaseHas('continents', ['geoname_id' => 6255148]);
    $this->assertDatabaseCount('countries', 0);
});

test('get status returns sync information', function () {
    $manager = createManager();

    $status = $manager->getStatus();

    expect($status)->toHaveKeys(['continents', 'countries', 'divisions', 'cities']);
    expect($status['continents']['in_sync'])->toBeFalse();
    expect($status['continents']['remote_count'])->toBe(1);
});

test('auto mode resolves to incremental when local data exists', function () {
    $manager = createManager();

    // First sync populates data
    $manager->sync(force: true, mode: 'incremental');

    // Auto mode should use incremental since data exists
    $skipped = [];
    $manager->sync(mode: 'auto', onProgress: function (string $table, string $status) use (&$skipped): void {
        if ($status === 'skipped') {
            $skipped[] = $table;
        }
    });

    expect($skipped)->toContain('continents', 'countries', 'divisions', 'cities');
});
