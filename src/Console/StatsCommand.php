<?php

namespace Kaal\Realtime\Console;

use Illuminate\Console\Command;

class StatsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kaal:stats';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show KAAL realtime broadcast statistics';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('KAAL Realtime Stats');

        // To do this fully we would fetch from the gateway or cache.
        // For now, we will mock or display basic info.
        $this->line('- Broadcasting Connection: ' . config('broadcasting.default'));
        $this->line('- KAAL Gateway URL: ' . config('broadcasting.connections.kaal.url', 'Not configured'));
        
        $this->info("\nHint: To view real-time traffic, check the Gateway Dashboard at " . config('broadcasting.connections.kaal.url') . "/dashboard");

        return 0;
    }
}
