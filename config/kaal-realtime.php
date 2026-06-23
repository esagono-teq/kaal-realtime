<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will be assigned to every realtime route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | PWA Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the KAAL Realtime PWA module.
    |
    */
    
    'pwa' => [
        'enable' => true,
        'manifest' => [
            'name' => env('APP_NAME', 'KAAL Realtime App'),
            'short_name' => 'KAAL',
            'start_url' => '/',
            'display' => 'standalone',
            'theme_color' => '#ffffff',
            'background_color' => '#ffffff',
            'icons' => [
                [
                    'src' => '/vendor/kaal-realtime/pwa/icon.svg',
                    'sizes' => '192x192',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => '/vendor/kaal-realtime/pwa/icon.svg',
                    'sizes' => '512x512',
                    'type' => 'image/svg+xml',
                    'purpose' => 'any maskable'
                ]
            ],
        ],
        'offline' => [
            'fallback_page' => '/offline',
            'cache_routes' => [
                '/' => 'NetworkFirst',
            ],
            'cache_assets' => 'CacheFirst',
        ],
        'push' => [
            'vapid_public_key' => env('VAPID_PUBLIC_KEY'),
            'vapid_private_key' => env('VAPID_PRIVATE_KEY'),
            'subject' => env('VAPID_SUBJECT', 'mailto:admin@example.com'),
        ],
        'sync' => [
            'enable' => true,
        ],
    ],
];
