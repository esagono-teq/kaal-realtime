<?php

namespace Kaal\Realtime\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Kaal\Realtime\Facades\Realtime;

class RealtimeRefreshController extends Controller
{
    /**
     * Refresh a specific realtime block.
     *
     * @param string $id
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function __invoke(string $id)
    {
        Log::info('KAAL: Refresh Request', [
            'id' => $id
        ]);

        $block = Realtime::get($id);

        if (!$block) {
            abort(404, "Realtime block [{$id}] not found.");
        }

        $handlerClass = $block['handler'];
        $handler = app($handlerClass);

        return response($handler->render());
    }
}
