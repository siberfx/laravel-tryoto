<?php

namespace Siberfx\LaravelTryoto\app\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TryotoService
{
    private string $url;
    private string $_token;
    private string $accessToken;
    private string $_webhook;
    private string $cacheName;
    private int $cacheTime;

    public function __construct()
    {
        if (!config('laravel-tryoto.tryoto.sandbox')) {
            $this->url = config('laravel-tryoto.tryoto.live.url');
            $this->_token = config('laravel-tryoto.tryoto.live.token');
            $this->_webhook = route('tryoto.callback'); // comes from package route.
        } else {
            $this->url = config('laravel-tryoto.tryoto.test.url');
            $this->_token = config('laravel-tryoto.tryoto.test.token');
            $this->_webhook = 'https://request-dinleyici-url-buraya-yazilmali';
        }
        $this->cacheName = config('laravel-tryoto.tryoto.cache_name');
        $this->cacheTime = (int) config('laravel-tryoto.tryoto.cache_time');

        $this->accessToken = $this->authorize();
    }


    public function authorize(): string
    {

        if (Cache::has($this->cacheName)) {
            return Cache::get($this->cacheName);
        }

        $response = Http::post($this->url . '/rest/v2/refreshToken', [
            'refresh_token' => $this->_token,
        ]);

        $token = 'Bearer ' . $response->json()['access_token'];

        Cache::put($this->cacheName, $token, now()->addMinutes($this->cacheTime));

        return $token;
    }


    public function listOrders(int $page = 1, array $filters = [])
    {
        $query = array_merge([
            'perPage' => 100,
            'page' => $page,
        ], array_filter($filters, static fn ($value) => $value !== null && $value !== ''));

        $response = Http::withHeaders([
            'Authorization' => $this->accessToken
        ])
            ->get($this->url . '/rest/v2/orders', $query);

        return json_decode($response->body());
    }


    public function orderDetail($orderId = null)
    {
        if (empty($orderId)) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => $this->accessToken
        ])
            ->get($this->url . '/rest/v2/orderDetails?orderId=' . $orderId);

        return json_decode($response->body(), false, 512, JSON_THROW_ON_ERROR);
    }

    public function cancelOrder($orderId)
    {
        if (empty($orderId)) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => $this->accessToken,
            'Accept' => 'application/json',
        ])
            ->post($this->url . '/rest/v2/cancelOrder', [
                'orderId' => $orderId
            ]);

        return $response->json();
    }


    public function holdOrder($orderId, string $reason = '', string $reasonLang = 'en')
    {
        if (empty($orderId)) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => $this->accessToken,
            'Accept' => 'application/json',
        ])
            ->post($this->url . '/rest/v2/holdOrder', [
                'orderId' => $orderId,
                'onHoldReason' => $reason,
                'onHoldReasonLang' => $reasonLang,
            ]);

        return $response->json();
    }


    public function unHoldOrder($orderId)
    {
        if (empty($orderId)) {
            return [];
        }

        $response = Http::withHeaders([
            'Authorization' => $this->accessToken,
            'Accept' => 'application/json',
        ])
            ->post($this->url . '/rest/v2/unHoldOrder', [
                'orderId' => $orderId,
            ]);

        return $response->json();
    }


    public function updateOrderStatus($orderIds, string $status, string $description = '', ?string $date = null)
    {
        if (empty($orderIds) || $status === '') {
            return [];
        }

        $body = [
            'orderIds' => $orderIds,
            'status' => $status,
        ];

        if ($description !== '') {
            $body['description'] = $description;
        }

        if (!empty($date)) {
            $body['date'] = $date;
        }

        $response = Http::withHeaders([
            'Authorization' => $this->accessToken,
            'Accept' => 'application/json',
        ])
            ->post($this->url . '/rest/v2/updateOrderStatus', $body);

        return $response->json();
    }


    public function createOrder($body)
    {

        $response = Http::withHeaders([
            'Authorization' => $this->accessToken,
            'Accept' => 'application/json',
        ])
            ->post($this->url . '/rest/v2/createOrder', $body);

        return $response->json();
    }


    public function updateOrder($body)
    {

        $response = Http::withHeaders([
            'Authorization' => $this->accessToken,
            'Accept' => 'application/json',
        ])
            ->post($this->url . '/rest/v2/updateOrder', $body);

        return $response->json();
    }


    public function setWebhook()
    {
        return Http::post($this->url . '/rest/v2/webhook', [
            "method" => "post",
            "url" => $this->_webhook,
            "orderPrefix" => "",
            "timestampFormat" => "yyyy-MM-dd HH:mm:ss",
            "secretKey" => "",
            "authorizationKey" => "",
            "webhookType" => "orderStatus"
        ]);
    }

}
