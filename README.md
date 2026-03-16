# Laravel Geo

[![Tests](https://github.com/alfonsobries/laravel-geo/actions/workflows/tests.yml/badge.svg)](https://github.com/alfonsobries/laravel-geo/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/alfonsobries/laravel-geo.svg)](https://packagist.org/packages/alfonsobries/laravel-geo)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Geo-location data package for Laravel. Syncs continents, countries, divisions, cities (with translations), and a MaxMind IP geolocation database from [geo.vexilo.com](https://geo.vexilo.com). Everything runs locally after sync.

## Installation

```bash
composer require alfonsobries/laravel-geo
php artisan vendor:publish --tag=geo-config
php artisan vendor:publish --tag=geo-migrations
php artisan migrate
```

## Configuration

Add to your `.env`:

```
GEO_API_URL=https://geo.vexilo.com
GEO_API_KEY=your-secret-key-here
```

Full config at `config/geo.php`:

```php
return [
    'api_url' => env('GEO_API_URL', 'https://geo.vexilo.com'),
    'api_key' => env('GEO_API_KEY'),

    'sync' => [
        'continents' => true,
        'countries' => true,
        'divisions' => true,
        'cities' => true,
    ],

    'locales' => null, // ['en', 'es'] or null for all
    'connection' => null, // database connection override

    'maxmind' => [
        'database_path' => storage_path('app/geoip/GeoLite2-City.mmdb'),
    ],

    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // IP lookup cache in seconds
    ],
];
```

## Syncing Data

```bash
# Sync everything (geo tables + MaxMind database)
php artisan geo:sync

# Force full re-sync
php artisan geo:sync --force

# Use dump mode (fast initial sync via CSV files)
php artisan geo:sync --mode=dump

# Use incremental mode (daily updates via watermark)
php artisan geo:sync --mode=incremental

# Sync specific tables only
php artisan geo:sync --tables=countries,divisions

# Skip MaxMind database sync
php artisan geo:sync --no-maxmind

# Check sync status
php artisan geo:status
```

### Sync Modes

- **Auto** (default) — Detects the best mode. Uses dump for initial sync (no local data), incremental for daily updates.
- **Dump** — Downloads CSV dump files from the server. Fast bulk import with truncate + reimport. Best for initial setup or full re-sync.
- **Incremental** — Uses `updated_after` watermarks to only fetch changed records. Fetches deletions from the server. Best for daily scheduled updates.

The sync is:
- **Manifest-driven** — compares checksums, skips tables that haven't changed
- **Idempotent** — uses upserts keyed on `geoname_id` / `alternate_name_id`
- **Dependency-aware** — syncs in order: continents -> countries -> divisions -> cities
- **Retry-capable** — HTTP requests retry 3x with backoff on failure

Add to your scheduler for automatic updates:

```php
Schedule::command('geo:sync')->daily();
```

## IP Geolocation

Resolve IP addresses to local geo models using the synced MaxMind database. Zero API calls at runtime.

```php
use AlfonsoBries\Geo\Facades\Geo;

// From a specific IP
$location = Geo::locate('187.190.56.23');
$location->country();    // Country model
$location->division();   // Division model
$location->city();       // City model
$location->timezone;     // 'America/Mexico_City'
$location->latitude;     // 20.6597
$location->longitude;    // -103.3496

// From the current request
$country = Geo::country();
$code = Geo::countryCode(); // 'MX'
```

Results are cached by IP (configurable via `geo.cache.ttl`).

## Models

```php
use AlfonsoBries\Geo\Models\Continent;
use AlfonsoBries\Geo\Models\Country;
use AlfonsoBries\Geo\Models\Division;
use AlfonsoBries\Geo\Models\City;

// Finders
$mexico = Country::findByCode('MX');
$mexico = Country::findByIso('MEX');
$europe = Continent::findByCode('EU');
$jalisco = Division::findByName('Jalisco');
$gdl = City::findByName('Guadalajara');

// Relationships
$country->continent;       // Continent model
$country->divisions;       // Collection of Division
$country->cities;          // Collection of City
$country->translations;    // Collection of CountryTranslation
$country->getTranslation('es'); // "Mexico"
```

## Traits & Scopes

Add geo relationships and query scopes to your models:

```php
use AlfonsoBries\Geo\Traits\BelongsToCountry;
use AlfonsoBries\Geo\Traits\BelongsToDivision;
use AlfonsoBries\Geo\Traits\BelongsToCity;
use AlfonsoBries\Geo\Traits\BelongsToContinent;

class User extends Model
{
    use BelongsToCountry, BelongsToDivision, BelongsToCity;
}
```

Each trait provides a relationship and query scopes:

```php
// Relationships
$user->country;
$user->division;
$user->city;

// Scopes - by model
$mexico = Country::findByCode('MX');
User::whereCountry($mexico)->get();

// Scopes - by code/name (string)
User::whereCountry('MX')->get();
User::whereCountries(['MX', 'US', 'CA'])->get();
User::whereContinent('NA')->get();
User::whereDivision('Jalisco')->get();
User::whereCity('Guadalajara')->get();

// Combine scopes
User::whereCountry('MX')->whereDivision('Jalisco')->get();
```

## Factories

Available for testing:

```php
$continent = Continent::factory()->create();
$country = Country::factory()->create(['continent_id' => $continent->id]);
$division = Division::factory()->create(['country_id' => $country->id]);
$city = City::factory()->create([
    'country_id' => $country->id,
    'division_id' => $division->id,
]);
```

## Testing

```bash
composer install
vendor/bin/pest
```

## License

MIT
