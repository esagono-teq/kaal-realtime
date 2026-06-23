<?php

namespace Kaal\Realtime\Listeners;

use Illuminate\Support\Facades\Log;
use Kaal\Realtime\Events\ModelChanged;
use Kaal\Realtime\Events\RealtimeUpdate;

class ModelChangedListener
{
    /**
     * Handle the event.
     *
     * @param  ModelChanged  $event
     * @return void
     */
    public function handle(ModelChanged $event): void
    {
        Log::info('KAAL: ModelChanged Fired', [
            'model' => get_class($event->model)
        ]);

        RealtimeUpdate::dispatch(get_class($event->model));

        if (method_exists($event->model, 'broadcastToClusters')) {
            $clusters = $event->model->broadcastToClusters();
            if (is_array($clusters)) {
                foreach ($clusters as $cluster) {
                    \Kaal\Realtime\Cluster::refresh($cluster);
                }
            }
        }
    }
}
