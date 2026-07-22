<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RouteOptimizationService
{
    /**
     * Calculate optimal task sequence route using PostGIS spatial functions.
     *
     * @param string|int $workerId
     * @param float $startLat
     * @param float $startLng
     * @return array
     */
    public function calculateOptimalRoute($workerId, float $startLat, float $startLng): array
    {
        try {
            // Execute spatial SQL function
            $rawSequence = DB::select("SELECT * FROM get_optimized_task_sequence(?, ?, ?)", [
                $workerId,
                $startLat,
                $startLng,
            ]);

            $tasks = [];
            $coordinates = [
                [$startLng, $startLat], // Start origin coordinate
            ];

            $totalMeters = 0.0;

            foreach ($rawSequence as $item) {
                $lat = (float) $item->latitude;
                $lng = (float) $item->longitude;

                $tasks[] = [
                    'task_id'         => $item->task_id,
                    'title'           => $item->title,
                    'outlet_id'       => $item->outlet_id,
                    'outlet_name'     => $item->outlet_name,
                    'latitude'        => $lat,
                    'longitude'       => $lng,
                    'distance_meters' => (float) $item->distance_meters,
                    'sequence_order'  => (int) $item->sequence_order,
                ];

                $coordinates[] = [$lng, $lat];
                $totalMeters += (float) $item->distance_meters;
            }

            $totalKm = round($totalMeters / 1000, 2);

            // Estimated travel time in minutes assuming average urban speed of 30 km/h
            $estimatedTimeMins = round(($totalKm / 30) * 60);

            // GeoJSON LineString payload connecting start location to all task stopovers
            $geoJson = [
                'type' => 'FeatureCollection',
                'features' => [
                    [
                        'type' => 'Feature',
                        'geometry' => [
                            'type'        => 'LineString',
                            'coordinates' => $coordinates,
                        ],
                        'properties' => [
                            'worker_id'          => $workerId,
                            'total_distance_km'  => $totalKm,
                            'estimated_time_min' => $estimatedTimeMins,
                            'stopovers_count'    => count($tasks),
                        ],
                    ],
                ],
            ];

            return [
                'success'             => true,
                'tasks'               => $tasks,
                'total_distance_km'   => $totalKm,
                'estimated_time_mins' => $estimatedTimeMins,
                'geojson'             => $geoJson,
            ];

        } catch (\Exception $e) {
            Log::error('Route optimization failed: ' . $e->getMessage());

            return [
                'success'             => false,
                'message'             => 'Route calculation failed.',
                'error'               => $e->getMessage(),
                'tasks'               => [],
                'total_distance_km'   => 0.0,
                'estimated_time_mins' => 0,
                'geojson'             => null,
            ];
        }
    }
}
