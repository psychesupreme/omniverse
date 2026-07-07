<?php

namespace App\Jobs;

use App\Models\Geofence;
use App\Models\Tenant;
use App\Models\TrackingLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AnalyzeTrackingLogGeofences implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The tenant ID.
     *
     * @var string
     */
    protected string $tenantId;

    /**
     * The tracking log instance.
     *
     * @var TrackingLog
     */
    protected TrackingLog $trackingLog;

    /**
     * Create a new job instance.
     */
    public function __construct(string $tenantId, TrackingLog $trackingLog)
    {
        $this->tenantId = $tenantId;
        $this->trackingLog = $trackingLog;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Initialize tenant context
        $tenant = Tenant::find($this->tenantId);
        if ($tenant) {
            tenancy()->initialize($tenant);
        }

        // Find geofences containing the tracking log location
        $geofences = Geofence::whereRaw(
            "ST_Contains(boundary::geometry, ST_SetSRID(ST_MakePoint(?, ?), 4326))",
            [
                $this->trackingLog->location['longitude'],
                $this->trackingLog->location['latitude'],
            ]
        )->get();

        // Dispatch geofence alert events
        foreach ($geofences as $geofence) {
            \App\Events\GeofenceAlertTriggered::dispatch($this->tenantId, $this->trackingLog->user_id, $geofence->name);
        }
    }
}
