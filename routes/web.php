<?php

use Illuminate\Support\Facades\Route;
use Kaal\Realtime\Http\Controllers\RealtimeRefreshController;

Route::post('/kaal/presence/heartbeat', [\Kaal\Realtime\Http\Controllers\PresenceController::class, 'heartbeat'])
    ->middleware(['web'])
    ->name('kaal.presence.heartbeat');

Route::post('/kaal/presence/leave', [\Kaal\Realtime\Http\Controllers\PresenceController::class, 'leave'])
    ->middleware(['web'])
    ->name('kaal.presence.leave');

Route::post('/kaal/realtime/action/{name}', \Kaal\Realtime\Http\Controllers\RealtimeActionController::class)
    ->middleware(['web'])
    ->name('kaal.realtime.action');

// PWA Routes
Route::get('/manifest.json', [\Kaal\Realtime\Http\Controllers\PwaController::class, 'manifest'])
    ->name('kaal.manifest');

Route::get('/sw.js', [\Kaal\Realtime\Http\Controllers\PwaController::class, 'serviceWorker'])
    ->name('kaal.sw');

Route::get('/offline', [\Kaal\Realtime\Http\Controllers\PwaController::class, 'offline'])
    ->name('kaal.offline');
