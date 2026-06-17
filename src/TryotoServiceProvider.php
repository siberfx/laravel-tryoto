<?php

namespace Siberfx\LaravelTryoto;

use Illuminate\Support\ServiceProvider;

class TryotoServiceProvider extends ServiceProvider
{
    public string $routeFilePath = '/routes/tryoto.php';

    /**
     * Register package services and merge configuration.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/laravel-tryoto.php',
            'laravel-tryoto'
        );
    }

    /**
     * Bootstrap package routes and publishable assets.
     */
    public function boot(): void
    {
        $this->setupRoutes();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/laravel-tryoto.php' => config_path('laravel-tryoto.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . $this->routeFilePath => base_path($this->routeFilePath),
            ], 'routes');
        }
    }

    /**
     * Load the package routes, preferring an app-level override if present.
     */
    public function setupRoutes(): void
    {
        // by default, use the routes file provided in the package
        $routeFilePathInUse = __DIR__ . $this->routeFilePath;

        // but if there's a file with the same name in the app's routes, use that one
        if (file_exists(base_path() . $this->routeFilePath)) {
            $routeFilePathInUse = base_path() . $this->routeFilePath;
        }

        $this->loadRoutesFrom($routeFilePathInUse);
    }
}
