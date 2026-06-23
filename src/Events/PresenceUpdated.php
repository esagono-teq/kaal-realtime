<?php

namespace Kaal\Realtime\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PresenceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $room;
    public string $type; // 'join' or 'leave'
    public array $payload;

    /**
     * Create a new event instance.
     *
     * @param string $room
     * @param string $type
     * @param array $payload
     */
    public function __construct(string $room, string $type, array $payload)
    {
        $this->room = $room;
        $this->type = $type;
        $this->payload = $payload;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        // Use a presence channel named after the room
        return new PresenceChannel('kaal-presence-' . $this->room);
    }

    public function broadcastAs()
    {
        return 'kaal.presence.updated';
    }
}
