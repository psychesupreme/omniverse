<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\RouteOptimizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RouteOptimizationController extends Controller
{
    protected RouteOptimizationService $routeService;

    public function __construct(RouteOptimizationService $routeService)
    {
        $this->routeService = $routeService;
    }

    /**
     * Optimize multi-stop delivery/task route for a field worker.
     */
    public function optimize(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'worker_id'       => ['required'],
            'start_latitude'  => ['required', 'numeric', 'between:-90,90'],
            'start_longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $result = $this->routeService->calculateOptimalRoute(
            $validated['worker_id'],
            (float) $validated['start_latitude'],
            (float) $validated['start_longitude']
        );

        if (! $result['success']) {
            return response()->json([
                'message' => $result['message'] ?? 'Route optimization failed.',
                'error'   => $result['error'] ?? null,
            ], 500);
        }

        return response()->json([
            'tasks'               => $result['tasks'],
            'total_distance_km'   => $result['total_distance_km'],
            'estimated_time_mins' => $result['estimated_time_mins'],
            'geojson'             => $result['geojson'],
        ]);
    }
}
