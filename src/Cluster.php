<?php

namespace Kaal\Realtime;

use Illuminate\Support\Facades\Event;
use Kaal\Realtime\Events\ClusterRefreshed;

class Cluster
{
    /**
     * Refresh a cluster by name.
     *
     * @param string $name
     * @return void
     */
    public function refresh(string $name): void
    {
        Event::dispatch(new ClusterRefreshed($name));
    }

    /**
     * Get statistics for a given cluster.
     *
     * @param string $name
     * @return array
     */
    public function stats(string $name): array
    {
        // TODO: Implement with Kaal Gateway / Reverb API
        return [
            'cluster' => $name,
            'connections' => 0,
            'messages_per_second' => 0,
        ];
    }

    /**
     * Get active channels in a cluster.
     *
     * @param string $name
     * @return array
     */
    public function channels(string $name): array
    {
        // TODO: Implement with Kaal Gateway / Reverb API
        return [];
    }
}
