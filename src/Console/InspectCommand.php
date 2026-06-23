<?php

namespace Kaal\Realtime\Console;

use Illuminate\Console\Command;
use Kaal\Realtime\Facades\Realtime;

class InspectCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kaal:inspect';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Inspect registered KAAL realtime blocks and server actions';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('KAAL Realtime Inspection');

        $this->info("\n--- Registered Blocks ---");
        $blocks = Realtime::all();
        if (empty($blocks)) {
            $this->line('No manual blocks registered.');
        } else {
            foreach ($blocks as $id => $data) {
                $this->line("- [{$id}] handler: {$data['handler']}, models: " . implode(', ', $data['models']));
            }
        }

        $this->info("\n--- Server Actions ---");
        $actions = Realtime::allActions();
        if (empty($actions)) {
            $this->line('No server actions registered.');
        } else {
            foreach (array_keys($actions) as $name) {
                $this->line("- Action: {$name}");
            }
        }

        return 0;
    }
}
