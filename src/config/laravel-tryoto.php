<?php

return [

    'tryoto' => [
        'cache_name' => 'oto_api_token',
        'cache_time' => 58, // time in minutes, OTO access tokens are valid for 1 hour
        'timeout' => env('TRYOTO_TIMEOUT', 30), // HTTP timeout in seconds
        'sandbox' => env('TRYOTO_SANDBOX', false),
        'test' => [
            'url' => env('TRYOTO_TEST_URL', 'https://staging-api.tryoto.com'),
            'token' => env('TRYOTO_TEST_REFRESH_TOKEN', 'xxxx'),
        ],
        'live' => [
            'url' => env('TRYOTO_URL', 'https://api.tryoto.com'),
            'token' => env('TRYOTO_REFRESH_TOKEN', 'xxxxxxxxxxxxxxxxx'),
        ],

        // used by setWebhook()/updateWebhook() and to validate incoming webhook calls
        'webhook' => [
            'url' => env('TRYOTO_WEBHOOK_URL'), // defaults to the package's tryoto.callback route
            'type' => env('TRYOTO_WEBHOOK_TYPE', 'orderStatus'), // orderStatus, shipmentError, newOrders, walletTransaction
            'secret_key' => env('TRYOTO_WEBHOOK_SECRET'), // payload signature key (HmacSHA256)
            'verify_signature' => env('TRYOTO_WEBHOOK_VERIFY_SIGNATURE', false), // reject calls whose signature does not match secret_key
            'authorization_key' => env('TRYOTO_WEBHOOK_AUTHORIZATION_KEY'), // expected in the Authorization header
            'timestamp_format' => 'yyyy-MM-dd HH:mm:ss',
            'order_prefix' => '',
        ],
    ],

];
