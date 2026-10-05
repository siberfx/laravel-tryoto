<?php

namespace Siberfx\LaravelTryoto;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Siberfx\LaravelTryoto\app\Events\TryotoWebhookReceived;
use Siberfx\LaravelTryoto\app\Listeners\SendWebhookNotification;
use Siberfx\LaravelTryoto\app\Notifications\TryotoNotifier;
use Siberfx\LaravelTryoto\app\Services\TryotoService;

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

        $this->app->singleton(TryotoService::class);
        $this->app->singleton(TryotoNotifier::class);
    }

    /**
     * Bootstrap package routes, listeners and publishable assets.
     */
    public function boot(): void
    {
        $this->setupRoutes();

        $this->loadTranslationsFrom(__DIR__ . '/lang', 'tryoto');

        // no-op until a Slack or Telegram channel is configured
        Event::listen(TryotoWebhookReceived::class, SendWebhookNotification::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/config/laravel-tryoto.php' => config_path('laravel-tryoto.php'),
            ], 'config');

            $this->publishes([
                __DIR__ . $this->routeFilePath => base_path($this->routeFilePath),
            ], 'routes');

            $this->publishes([
                __DIR__ . '/lang' => $this->app->langPath('vendor/tryoto'),
            ], 'lang');
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
