<?php

namespace App\Services;

use App\Events\WorkerEnteredGeofence;
use App\Events\WorkerExitedGeofence;
use App\Models\Geofence;
use App\Models\GeofenceLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GeofenceEvaluationService
{
    /**
     * Evaluate field worker location telemetry coordinates for geofence entry/exit crossings.
     *
     * @param User $worker
     * @param float $lat
     * @param float $lng
     * @param string $timestamp
     * @return void
     */
    public function evaluateLocation(User $worker, float $lat, float $lng, string $timestamp): void
    {
        try {
            $occurredAt = Carbon::parse($timestamp);

            // 1. Fetch all active geofences in the tenant database
            $allGeofences = Geofence::where('is_active', true)->whereNotNull('area')->get();
            if ($allGeofences->isEmpty()) {
                return;
            }

            // 2. Fetch geofences containing the specified coordinates (PostGIS ST_Contains expects longitude first)
            $insideGeofences = Geofence::where('is_active', true)
                ->whereNotNull('area')
                ->whereRaw(
                    "ST_Contains(area::geometry, ST_SetSRID(ST_MakePoint(?, ?), 4326))",
                    [$lng, $lat]
                )->get();

            $insideIds = $insideGeofences->pluck('id')->toArray();

            // 3. Evaluate state crossings for each active geofence
            foreach ($allGeofences as $geofence) {
                try {
                    $lastLog = GeofenceLog::where('user_id', $worker->id)
                        ->where('geofence_id', $geofence->id)
                        ->latest('occurred_at')
                        ->first();

                    $isInsideNow = in_array($geofence->id, $insideIds);

                    if ($isInsideNow) {
                        // Entry Condition: Worker is inside, and last recorded event was exit or doesn't exist
                        if ($lastLog === null || $lastLog->event_type === 'exit') {
                            GeofenceLog::create([
                                'geofence_id' => $geofence->id,
                                'user_id'     => $worker->id,
                                'event_type'  => 'entry',
                                'occurred_at' => $occurredAt,
                            ]);

                            WorkerEnteredGeofence::dispatch($worker, $geofence, $occurredAt);
                        }
                    } else {
                        // Exit Condition: Worker is outside, and last recorded event was entry
                        if ($lastLog !== null && $lastLog->event_type === 'entry') {
                            GeofenceLog::create([
                                'geofence_id' => $geofence->id,
                                'user_id'     => $worker->id,
                                'event_type'  => 'exit',
                                'occurred_at' => $occurredAt,
                            ]);

                            WorkerExitedGeofence::dispatch($worker, $geofence, $occurredAt);
                        }
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to evaluate crossing check for Geofence ID {$geofence->id}: " . $e->getMessage());
                }
            }

        } catch (\Exception $e) {
            Log::error("Geofence evaluation service general failure for worker ID {$worker->id}: " . $e->getMessage());
        }
    }
}
