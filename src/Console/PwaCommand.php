<?php

namespace Kaal\Realtime\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PwaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kaal:pwa';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish KAAL Realtime PWA assets and configuration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Publishing KAAL Realtime PWA assets...');

        // Publish configuration
        $this->call('vendor:publish', [
            '--tag' => 'kaal-realtime-config',
            '--force' => true,
        ]);

        // Publish JS assets
        $this->call('vendor:publish', [
            '--tag' => 'kaal-realtime-assets',
            '--force' => true,
        ]);

        // Publish files that are loaded directly by the browser and service worker.
        $this->call('vendor:publish', [
            '--tag' => 'kaal-realtime-pwa-assets',
            '--force' => true,
        ]);

        // Publish Views (Offline page, UI components)
        $this->call('vendor:publish', [
            '--tag' => 'kaal-realtime-views',
            '--force' => false,
        ]);

        // Publish Icons
        $publicPwaPath = public_path('vendor/kaal-realtime/pwa');
        if (!File::exists($publicPwaPath)) {
            File::makeDirectory($publicPwaPath, 0755, true);
        }

        // Output success message
        $this->info('KAAL Realtime PWA assets published successfully.');
        $this->line('Make sure to add @realtimePwa to your layout file.');
    }
}
