<?php

namespace App\Providers;

use App\Services\Routing\HereRouteDistanceAdapter;
use App\Services\Routing\RouteDistanceAdapter;
use App\Support\RuntimeDatabasePolicy;
use Illuminate\Database\ConfigurationUrlParser;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(RouteDistanceAdapter::class, HereRouteDistanceAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $connection = (string) config('database.default');
        $configuration = (new ConfigurationUrlParser)->parseConfiguration(
            config("database.connections.{$connection}"),
        );

        RuntimeDatabasePolicy::assertSupported(
            $this->app->environment(),
            (string) $configuration['driver'],
            (string) $configuration['database'],
            (bool) config('database.postgres_integration_test'),
        );
    }
}
