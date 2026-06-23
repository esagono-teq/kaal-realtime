<?php

namespace Kaal\Realtime\Facades;

use Illuminate\Support\Facades\Facade;

class Cluster extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'kaal-cluster';
    }
}
