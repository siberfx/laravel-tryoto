<?php

namespace Siberfx\LaravelTryoto;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class TryotoServiceProvider extends ServiceProvider
{

    protected $defer = true;

    protected $packageName = 'LaravelTryoto';

    public $routeFilePath = '/routes/tryoto.php';

    public function boot()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/config/laravel-tryoto.php',
            'laravel-tryoto'
        );

        $this->publishes([__DIR__ . '/config' => config_path()], 'config');

        $this->publishes([__DIR__ . $this->routeFilePath => base_path($this->routeFilePath)], 'routes');

    }

    /**
     * Define the routes for the application.
     *
     * @param Router $router
     *
     * @return void
     */
    public function setupRoutes(Router $router)
    {
        // by default, use the routes file provided in vendor
        $routeFilePathInUse = __DIR__ . $this->routeFilePath;

        // but if there's a file with the same name in routes, use that one
        if (file_exists(base_path() . $this->routeFilePath)) {
            $routeFilePathInUse = base_path() . $this->routeFilePath;
        }

        $this->loadRoutesFrom($routeFilePathInUse);
    }

    public function register()
    {
        //
    }

    public function provides()
    {
        return ['tryoto'];
    }
}
