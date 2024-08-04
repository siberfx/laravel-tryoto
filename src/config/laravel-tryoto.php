<?php

return [

    'tryoto' => [
        'cache_name' => 'oto_api_token',
        'cache_time' => 58, // time in minutes
        'sandbox' => env('TRYOTO_SANDBOX', false),
        'test' => [
            'url' => env('TRYOTO_TEST_URL', 'https://staging-api.tryoto.com'),
            'token' => env('TRYOTO_TEST_REFRESH_TOKEN', 'xxxx'),
        ],
        'live' => [
            'url' => env('TRYOTO_URL', 'https://api.tryoto.com'),
            'token' => env('TRYOTO_REFRESH_TOKEN', 'xxxxxxxxxxxxxxxxx'),
        ],
    ],

];
