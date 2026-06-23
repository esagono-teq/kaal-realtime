<?php

namespace Kaal\Realtime\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ClusterRefreshEvent implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public string $cluster;

    /**
     * Create a new event instance.
     *
     * @param string $cluster
     */
    public function __construct(string $cluster)
    {
        $this->cluster = $cluster;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array
     */
    public function broadcastOn(): array
    {
        return ['kaal-realtime.clusters'];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'ClusterRefresh';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'cluster' => $this->cluster,
        ];
    }
}
