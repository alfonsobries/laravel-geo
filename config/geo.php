<?php

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
];
