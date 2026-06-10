<?php

return [
    'default' => env('CACHE_STORE', 'database'),
    'stores' => [
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
        'database' => [
            'driver' => 'database',
            'table' => env('DB_CACHE_TABLE', 'cache'),
            'connection' => env('DB_CACHE_CONNECTION'),
            'lock_connection' => env('DB_CACHE_LOCK_CONNECTION'),
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => env('REDIS_CACHE_CONNECTION', 'cache'),
            'lock_connection' => env('REDIS_CACHE_LOCK_CONNECTION', 'default'),
        ],
    ],
    'prefix' => env('CACHE_PREFIX', 'family_tree_cache_'),
];
