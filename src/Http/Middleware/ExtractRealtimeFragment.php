<?php

namespace Kaal\Realtime\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ExtractRealtimeFragment
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $fragmentId = $request->header('X-KAAL-FRAGMENT');

        // Pass down the stack if not a realtime fragment request
        if (!$fragmentId) {
            return $next($request);
        }

        // Validate the signature
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid Kaal Realtime signature.');
        }

        // Validate fragment ID format — must be kaal-fragment-{integer}
        // This prevents arbitrary fragment names from being injected
        if (!preg_match('/^kaal-fragment-\d+$/', $fragmentId)) {
            abort(403, 'Invalid Kaal fragment identifier.');
        }

        $response = $next($request);

        // Intercept view response to return only the fragment
        if ($response instanceof Response && $response->original instanceof \Illuminate\View\View) {
            // Attempt to extract the specific named fragment
            try {
                $fragmentHtml = $response->original->fragment($fragmentId);

                // If the fragment is empty or equals the full rendered view,
                // the fragment name was not found — refuse to return full content
                $fullHtml = $response->original->render();
                if ($fragmentHtml === $fullHtml || trim($fragmentHtml) === '') {
                    abort(404, "Kaal fragment [{$fragmentId}] not found in view.");
                }

                $response->setContent($fragmentHtml);
            } catch (\Throwable $e) {
                abort(404, "Kaal fragment [{$fragmentId}] extraction failed.");
            }
        }

        return $response;
    }
}
