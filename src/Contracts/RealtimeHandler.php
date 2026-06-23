<?php

namespace Kaal\Realtime\Contracts;

interface RealtimeHandler
{
    /**
     * Render the realtime block.
     *
     * @return string
     */
    public function render(): string;
}
