<?php

namespace Kaal\Realtime\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Access\AuthorizationException;
use Kaal\Realtime\Facades\Realtime;

class RealtimeActionController extends Controller
{
    /**
     * Handle executing a server action.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $name
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, string $name)
    {
        $action = Realtime::getAction($name);

        if (!$action) {
            return response()->json([
                'status' => 'error',
                'message' => "Server action [{$name}] not found."
            ], 404);
        }

        try {
            $result = $action($request);

            return response()->json([
                'status' => 'success',
                'data' => $result,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthorizationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage() ?: 'This action is unauthorized.',
            ], 403);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
