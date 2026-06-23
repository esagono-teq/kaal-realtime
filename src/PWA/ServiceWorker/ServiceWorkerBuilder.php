<?php

namespace Kaal\Realtime\PWA\ServiceWorker;

use Illuminate\Support\Facades\Config;

class ServiceWorkerBuilder
{
    public function generate(): string
    {
        $config = Config::get('kaal-realtime.pwa', []);
        
        // Output standard template that we will create in JS
        $js = "importScripts('/vendor/kaal-realtime/pwa/kaal-sw-core.js');\n";
        
        // Pass configuration to SW
        $js .= "const KAAL_SW_CONFIG = " . json_encode($config) . ";\n";
        
        $js .= "self.kaalSW = new KaalServiceWorker(KAAL_SW_CONFIG);\n";
        $js .= "self.kaalSW.register();\n";
        
        return $js;
    }
}
