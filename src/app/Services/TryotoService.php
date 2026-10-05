<?php

namespace Siberfx\LaravelTryoto\app\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Siberfx\LaravelTryoto\app\Exceptions\TryotoException;
use Siberfx\LaravelTryoto\app\Services\Concerns\ManagesAccount;
use Siberfx\LaravelTryoto\app\Services\Concerns\ManagesLocations;
use Siberfx\LaravelTryoto\app\Services\Concerns\ManagesOrders;
use Siberfx\LaravelTryoto\app\Services\Concerns\ManagesPickupLocations;
use Siberfx\LaravelTryoto\app\Services\Concerns\ManagesProducts;
use Siberfx\LaravelTryoto\app\Services\Concerns\ManagesReturns;
use Siberfx\LaravelTryoto\app\Services\Concerns\ManagesShipments;
use Siberfx\LaravelTryoto\app\Services\Concerns\ManagesStock;
use Siberfx\LaravelTryoto\app\Services\Concerns\ManagesWebhooks;

/**
 * Client for the OTO REST API v2 (https://apis.tryoto.com).
 *
 * Endpoints are grouped into traits under Concerns/, mirroring the sections of the official
 * documentation. Anything not wrapped there can still be reached through request()/call().
 */
class TryotoService
{
    use ManagesAccount;
    use ManagesLocations;
    use ManagesOrders;
    use ManagesPickupLocations;
    use ManagesProducts;
    use ManagesReturns;
    use ManagesShipments;
    use ManagesStock;
    use ManagesWebhooks;

    private string $url;
    private string $_token;
    private ?string $accessToken = null;
    private string $cacheName;
    private int $cacheTime;
    private int $timeout;

    public function __construct()
    {
        $environment = config('laravel-tryoto.tryoto.sandbox') ? 'test' : 'live';

        $this->url = rtrim((string) config("laravel-tryoto.tryoto.{$environment}.url"), '/');
        $this->_token = (string) config("laravel-tryoto.tryoto.{$environment}.token");

        // separate cache entries so switching sandbox on/off never reuses the other environment's token
        $this->cacheName = config('laravel-tryoto.tryoto.cache_name') . ($environment === 'test' ? '_sandbox' : '');
        $this->cacheTime = (int) config('laravel-tryoto.tryoto.cache_time');
        $this->timeout = (int) config('laravel-tryoto.tryoto.timeout', 30);
    }


    /**
     * Exchange the refresh token for an access token (valid 1 hour) and cache it.
     *
     * @return string the full Authorization header value ("Bearer ...")
     */
    public function authorize(bool $fresh = false): string
    {
        if ($fresh) {
            Cache::forget($this->cacheName);
            $this->accessToken = null;
        }

        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        if (Cache::has($this->cacheName)) {
            return $this->accessToken = Cache::get($this->cacheName);
        }

        $response = Http::acceptJson()
            ->timeout($this->timeout)
            ->post($this->url . '/rest/v2/refreshToken', [
                'refresh_token' => $this->_token,
            ]);

        if (!$response->successful() || !$response->json('access_token')) {
            throw TryotoException::authorizationFailed($response);
        }

        $token = 'Bearer ' . $response->json('access_token');

        Cache::put($this->cacheName, $token, now()->addMinutes($this->cacheTime));

        return $this->accessToken = $token;
    }


    /**
     * Send an authenticated request to any OTO endpoint.
     *
     * A 401 response refreshes the access token once and retries, so an expired cached token
     * never surfaces to the caller.
     *
     * @param  string  $path  path relative to the base URL, e.g. "/rest/v2/orders"
     * @param  array  $data  JSON body
     * @param  array  $query  query string parameters
     */
    public function request(string $method, string $path, array $data = [], array $query = []): Response
    {
        $options = [];

        $query = self::withoutEmpty($query);
        if ($query !== []) {
            $options['query'] = $query;
        }

        if ($data !== []) {
            $options['json'] = $data;
        }

        $url = $this->url . '/' . ltrim($path, '/');

        $response = $this->http()->send(strtoupper($method), $url, $options);

        if ($response->status() === 401) {
            $this->authorize(fresh: true);
            $response = $this->http()->send(strtoupper($method), $url, $options);
        }

        return $response;
    }


    /**
     * Same as request(), returning the decoded JSON body as an array.
     */
    public function call(string $method, string $path, array $data = [], array $query = []): ?array
    {
        return $this->request($method, $path, $data, $query)->json();
    }


    protected function http(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout($this->timeout)
            ->withHeaders(['Authorization' => $this->authorize()]);
    }


    protected static function withoutEmpty(array $values): array
    {
        return array_filter($values, static fn ($value) => $value !== null && $value !== '');
    }

}
