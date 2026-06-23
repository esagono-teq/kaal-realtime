<?php

namespace Kaal\Realtime\PWA;

use Illuminate\Support\Facades\Config;

class PwaManager
{
    public function manifest(): array
    {
        return Config::get('kaal-realtime.pwa.manifest', []);
    }

    public function generateManifestJson(): string
    {
        return json_encode($this->manifest(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
    public function pwa(array $options = []): void
    {
        // This will be called from Blade to inject PWA scripts and meta tags
        // E.g., <link rel="manifest" href="/kaal/manifest.json">
        // <script src="/vendor/kaal-realtime/pwa.js"></script>
        
        $manifestUrl = route('kaal.manifest');
        echo '<link rel="manifest" href="' . e($manifestUrl) . '">';
        echo '<script src="/vendor/kaal-realtime/pwa/manager.js"></script>';
        
        // Pass options to window
        $config = array_merge(Config::get('kaal-realtime.pwa', []), $options);
        echo '<script>window.KAAL_PWA_CONFIG = ' . json_encode($config) . ';</script>';
    }

    public function notify($user, $notification)
    {
        // Integration with Web Push API
        // For demonstration, dispatch to queue or call PushManager
    }

    public function sync(string $modelClass)
    {
        // Register a model for realtime sync in the service worker and local DB
        // Can output meta tags or JS registration
        echo '<script>
            window.KAAL_PWA_SYNC = window.KAAL_PWA_SYNC || [];
            window.KAAL_PWA_SYNC.push("' . e($modelClass) . '");
        </script>';
    }
}
