<?php

namespace AlfonsoBries\Geo;

use AlfonsoBries\Geo\Commands\GeoStatusCommand;
use AlfonsoBries\Geo\Commands\GeoSyncCommand;
use Illuminate\Support\ServiceProvider;

class GeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/geo.php', 'geo');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/geo.php' => config_path('geo.php'),
        ], 'geo-config');

        $this->publishes([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], 'geo-migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GeoSyncCommand::class,
                GeoStatusCommand::class,
            ]);
        }
    }
}
