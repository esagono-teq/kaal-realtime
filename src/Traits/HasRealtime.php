<?php

namespace Kaal\Realtime\Traits;

use Kaal\Realtime\Observers\RealtimeObserver;

trait HasRealtime
{
    /**
     * Boot the trait and register the observer.
     */
    public static function bootHasRealtime(): void
    {
        static::observe(RealtimeObserver::class);
    }
}
