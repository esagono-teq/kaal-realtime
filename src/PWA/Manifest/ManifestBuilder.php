<?php

namespace Kaal\Realtime\PWA\Manifest;

use Illuminate\Support\Facades\Config;

class ManifestBuilder
{
    public function generate(): array
    {
        $manifest = Config::get('kaal-realtime.pwa.manifest', []);

        // Default icons if none provided
        if (empty($manifest['icons'])) {
            $manifest['icons'] = [
                [
                    'src' => '/vendor/kaal-realtime/pwa/icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ],
                [
                    'src' => '/vendor/kaal-realtime/pwa/icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any maskable'
                ]
            ];
        }

        return $manifest;
    }
}
