<?php

namespace Kaal\Realtime\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kaal\Realtime\Presence;

class PresenceController extends Controller
{
    /**
     * Update user heartbeat in the room (server-side cache only).
     * Live presence events are handled by the gateway WebSocket directly.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function heartbeat(Request $request)
    {
        $request->validate([
            'room' => 'required|string',
        ]);

        $room    = $request->input('room');
        $user    = auth()->user();
        $userId  = $user ? $user->getKey() : session()->getId();
        $name    = $user
            ? ($user->name ?? $user->email ?? "User #{$userId}")
            : 'Guest #' . substr(session()->getId(), 0, 6);

        $cacheKey = "kaal:presence:{$room}";
        $users    = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        $now      = time();
        $expiresAt = $now + 15;

        $users[$userId] = [
            'id'         => $userId,
            'name'       => $name,
            'expires_at' => $expiresAt,
        ];

        \Illuminate\Support\Facades\Cache::put($cacheKey, $users, now()->addMinutes(60));

        return response()->json([
            'status' => 'ok',
            'users'  => array_values(Presence::users($room)),
        ]);
    }

    /**
     * Remove user from the room cache.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function leave(Request $request)
    {
        $request->validate([
            'room' => 'required|string',
        ]);

        $room   = $request->input('room');
        $user   = auth()->user();
        $userId = $user ? $user->getKey() : session()->getId();

        $cacheKey = "kaal:presence:{$room}";
        $users    = \Illuminate\Support\Facades\Cache::get($cacheKey, []);

        if (isset($users[$userId])) {
            unset($users[$userId]);
            \Illuminate\Support\Facades\Cache::put($cacheKey, $users, now()->addMinutes(60));
        }

        return response()->json(['status' => 'ok']);
    }
}
