<?php

namespace Siberfx\LaravelTryoto\Tests;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use Siberfx\LaravelTryoto\app\Services\TryotoService;
use Siberfx\LaravelTryoto\TryotoServiceProvider;

abstract class TestCase extends Orchestra
{
    public const BASE_URL = 'https://api.tryoto.com';

    protected function getPackageProviders($app): array
    {
        return [TryotoServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('cache.default', 'array');
        $app['config']->set('laravel-tryoto.tryoto.sandbox', false);
        $app['config']->set('laravel-tryoto.tryoto.live.token', 'live-refresh-token');
        $app['config']->set('laravel-tryoto.tryoto.test.token', 'test-refresh-token');
    }

    /**
     * Fake the OTO API: the token exchange succeeds and every other endpoint answers $response.
     */
    protected function fakeOto(array $response = ['success' => true], array $routes = []): void
    {
        Http::fake(array_merge([
            '*/rest/v2/refreshToken' => Http::response(['access_token' => 'access-token']),
        ], $routes, [
            '*' => Http::response($response),
        ]));
    }

    protected function oto(): TryotoService
    {
        return $this->app->make(TryotoService::class);
    }
}
