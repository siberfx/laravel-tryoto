<?php

use Illuminate\Support\Facades\Route;
use Siberfx\LaravelTryoto\app\Services\TryotoService;
use Siberfx\LaravelTryoto\TryotoServiceProvider;

it('merges the package config', function () {
    expect(config('laravel-tryoto.tryoto'))
        ->toHaveKeys(['cache_name', 'cache_time', 'timeout', 'sandbox', 'test', 'live', 'webhook'])
        ->and(config('laravel-tryoto.tryoto.live.url'))->toBe('https://api.tryoto.com')
        ->and(config('laravel-tryoto.tryoto.test.url'))->toBe('https://staging-api.tryoto.com')
        ->and(config('laravel-tryoto.tryoto.cache_time'))->toBe(58);
});

it('binds the service as a singleton', function () {
    expect(app(TryotoService::class))->toBe(app(TryotoService::class));
});

it('registers the package routes', function () {
    expect(Route::has('tryoto.callback'))->toBeTrue()
        ->and(Route::has('tryoto.set-webhook'))->toBeTrue()
        ->and(route('tryoto.callback', absolute: false))->toBe('/tryoto/webhook/callback')
        ->and(Route::getRoutes()->getByName('tryoto.callback')->methods())->toContain('POST', 'PUT');
});

it('publishes the config and routes files', function () {
    expect(TryotoServiceProvider::pathsToPublish(TryotoServiceProvider::class, 'config'))
        ->toHaveCount(1)
        ->and(array_values(TryotoServiceProvider::pathsToPublish(TryotoServiceProvider::class, 'config'))[0])
        ->toBe(config_path('laravel-tryoto.php'))
        ->and(TryotoServiceProvider::pathsToPublish(TryotoServiceProvider::class, 'routes'))->toHaveCount(1);
});
