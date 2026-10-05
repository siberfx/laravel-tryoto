<?php

return [

    'tryoto' => [
        'cache_name' => 'oto_api_token',
        'cache_time' => 58, // time in minutes, OTO access tokens are valid for 1 hour
        'timeout' => env('TRYOTO_TIMEOUT', 30), // HTTP timeout in seconds
        'sandbox' => env('TRYOTO_SANDBOX', false),
        'test' => [
            'url' => env('TRYOTO_TEST_URL', 'https://staging-api.tryoto.com'),
            'token' => env('TRYOTO_TEST_REFRESH_TOKEN'),
        ],
        'live' => [
            'url' => env('TRYOTO_URL', 'https://api.tryoto.com'),
            'token' => env('TRYOTO_REFRESH_TOKEN'),
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

        // Forward incoming webhook events to chat channels. Every channel is off while its
        // credentials are null, so nothing is sent unless you opt in.
        'notifications' => [
            'locale' => env('TRYOTO_NOTIFICATIONS_LOCALE', 'en'), // message language: en (default) or tr
            'types' => null, // null = all webhook types, or e.g. ['orderStatus', 'shipmentError']
            'statuses' => null, // null = all order statuses, or e.g. ['delivered', 'returned'] (orderStatus only)
            'queue' => env('TRYOTO_NOTIFICATIONS_QUEUE'), // null = send immediately, or a queue name
            'queue_connection' => env('TRYOTO_NOTIFICATIONS_QUEUE_CONNECTION'), // null = default connection

            'slack' => [
                'webhook_url' => env('TRYOTO_SLACK_WEBHOOK_URL'), // Slack incoming webhook URL
                'channel' => env('TRYOTO_SLACK_CHANNEL'), // optional channel override (legacy webhooks only)
                'username' => env('TRYOTO_SLACK_USERNAME'), // optional bot name override (legacy webhooks only)
            ],

            'telegram' => [
                'bot_token' => env('TRYOTO_TELEGRAM_BOT_TOKEN'), // token from @BotFather
                'chat_id' => env('TRYOTO_TELEGRAM_CHAT_ID'), // user, group or channel id
                'thread_id' => env('TRYOTO_TELEGRAM_THREAD_ID'), // optional forum topic id
                'api_url' => env('TRYOTO_TELEGRAM_API_URL', 'https://api.telegram.org'),
            ],

            'mail' => [
                'to' => env('TRYOTO_MAIL_TO'), // comma separated recipients, e.g. "ops@shop.test,owner@shop.test"
                'mailer' => env('TRYOTO_MAIL_MAILER'), // null = the default mailer
                'from_address' => env('TRYOTO_MAIL_FROM_ADDRESS'), // null = mail.from.address
                'from_name' => env('TRYOTO_MAIL_FROM_NAME'), // null = mail.from.name
                'subject_prefix' => env('TRYOTO_MAIL_SUBJECT_PREFIX', '[OTO]'),
            ],
        ],
    ],

];
