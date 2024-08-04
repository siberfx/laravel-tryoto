<?php

return [

    'tryoto' => [
        'cache_name' => 'oto_api_token',
        'cache_time' => 58, // time in minutes
        'sandbox' => env('TRYOTO_SANDBOX', false),
        'test' => [
            'url' => env('TRYOTO_TEST_URL', 'https://staging-api.tryoto.com'),
            'token' => env('TRYOTO_TEST_REFRESH_TOKEN', 'AMf-vBzoCS9QibzKmxGfj73VwjQX6j0kc012Yc0bFiN4fHaH0VaqVoGar8TP76qekAXU9NvryY32_8Cq04aH4E-rBgm21LCIkGmPwV4UpjLP8_aDK2nvqtBJ5XwomtFhiMZObRyHz1V-3KNPBscmvlvnLWo2EEY0UwdcHIjcDrrsNZK-SLPkwkteEeJlE2X-DRz4AAXRuxWovFYm1wot7sxLhNvnFj0nmA'),
        ],
        'live' => [
            'url' => env('TRYOTO_URL', 'https://api.tryoto.com'),
            'token' => env('TRYOTO_REFRESH_TOKEN', 'AMf-vBzhOhO-ZESizFmHmc9P_iEacdq7Zk7VgvLF7acKwvLqVaFEKsWOnLnxHUdrdluUOi_3MT-6qGouraehw0mFg-_QYq8D_QBKRuV7GowixMQmxyqJXFaCofIOWgbIj52TSRC2KP-FUGAvN-uJbLVtWtwzD-VvEWOt6DL5qP2ALvIhDKhLZ_dzcKRsRmLlYakWYv_m1zuOXoSr7lpSK1i7F3nI69V2bg'),
        ],
    ],

];
