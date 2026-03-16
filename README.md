# Laravel Geo

[![Tests](https://github.com/alfonsobries/laravel-geo/actions/workflows/tests.yml/badge.svg)](https://github.com/alfonsobries/laravel-geo/actions/workflows/tests.yml)
[![Latest Version on Packagist](https://img.shields.io/packagist/v/alfonsobries/laravel-geo.svg)](https://packagist.org/packages/alfonsobries/laravel-geo)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

Geo-location data package for Laravel. Syncs continent, country, division, and city data (with translations) from [geo.vexilo.com](https://geo.vexilo.com).

## Installation

```bash
composer require alfonsobries/laravel-geo
```

## Publish Config & Migrations

```bash
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
    'locales' => null,  // ['en', 'es'] or null for all
    'connection' => null,  // database connection override
];
```

## Syncing Data

```bash
# Sync all tables (skips unchanged based on checksums)
php artisan geo:sync

# Force full re-sync
php artisan geo:sync --force

# Sync specific tables only
php artisan geo:sync --tables=countries,divisions

# Check sync status
php artisan geo:status
```

The sync is:
- **Manifest-driven** — compares checksums, skips tables that haven't changed
- **Resumable** — stores the last cursor, so interrupted syncs pick up where they left off
- **Idempotent** — uses upserts keyed on `geoname_id` / `alternate_name_id`
- **Dependency-aware** — syncs in order: continents → countries → divisions → cities

## Models

```php
use AlfonsoBries\Geo\Models\Continent;
use AlfonsoBries\Geo\Models\Country;
use AlfonsoBries\Geo\Models\Division;
use AlfonsoBries\Geo\Models\City;

$country = Country::where('code', 'MX')->first();
$country->continent;       // Continent model
$country->divisions;       // Collection of Division
$country->cities;          // Collection of City
$country->translations;    // Collection of CountryTranslation
$country->getTranslation('es'); // "México"
```

## Traits

Add geo relationships to your own models:

```php
use AlfonsoBries\Geo\Traits\BelongsToCountry;
use AlfonsoBries\Geo\Traits\BelongsToDivision;
use AlfonsoBries\Geo\Traits\BelongsToCity;

class User extends Model
{
    use BelongsToCountry, BelongsToDivision, BelongsToCity;
}

// $user->country, $user->division, $user->city
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
