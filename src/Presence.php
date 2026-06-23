<?php

namespace Kaal\Realtime;

use Illuminate\Support\Facades\Cache;

class Presence
{
    /**
     * Update the heartbeat of a user in the room.
     *
     * @param string $room
     * @param mixed $user
     * @return void
     */
    public static function heartbeat(string $room, $user = null): void
    {
        $users = Cache::get("kaal:presence:{$room}", []);
        $now = time();
        
        $valid = array_filter($users, fn($u) => $u['expires_at'] > $now);
        
        $id = $user ? $user->getAuthIdentifier() : request()->session()->getId();
        $userData = $user ? $user->toArray() : ['id' => $id, 'guest' => true];
        
        $valid[$id] = [
            'id' => $id,
            'user' => $userData,
            'expires_at' => $now + 15
        ];
        
        Cache::put("kaal:presence:{$room}", $valid, now()->addMinutes(1));
    }

    /**
     * Count the number of active users in a presence room.
     *
     * @param string $room
     * @return int
     */
    public static function count(string $room): int
    {
        $users = Cache::get("kaal:presence:{$room}", []);
        // Remove expired entries
        $now = time();
        $valid = array_filter($users, fn($u) => $u['expires_at'] > $now);
        return count($valid);
    }

    /**
     * Get the list of active users in a room.
     *
     * @param string $room
     * @return array
     */
    public static function users(string $room): array
    {
        $users = Cache::get("kaal:presence:{$room}", []);
        $now = time();
        $valid = array_filter($users, fn($u) => $u['expires_at'] > $now);
        // Return values indexed numerically
        return array_values($valid);
    }
}
