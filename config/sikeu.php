<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SIKEU Payment API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SIKEU Payment Gateway integration
    |
    */

    'api' => [
        'base_url' => env('SIKEU_API_BASE_URL', 'http://localhost:8080'),
        'timeout' => env('SIKEU_API_TIMEOUT', 30),
    ],

    'auth' => [
        'api_key' => env('SIKEU_API_KEY'),
        'shared_secret' => env('SIKEU_SHARED_SECRET'),
        'source_app' => env('SIKEU_SOURCE_APP', 'SIAKAD'),
    ],

    'payment' => [
        'default_provider' => env('SIKEU_DEFAULT_PROVIDER', 'BRI'),
        'default_qris_provider' => env('SIKEU_DEFAULT_QRIS_PROVIDER', 'BRI_QRIS'),
        'default_currency' => 'IDR',
    ],

    'logging' => [
        'enabled' => env('SIKEU_LOGGING_ENABLED', true),
        'channel' => env('SIKEU_LOG_CHANNEL', 'stack'),
    ],
];
