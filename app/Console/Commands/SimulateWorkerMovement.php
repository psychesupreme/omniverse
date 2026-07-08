<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TrackingLog;
use App\Services\SyncService;
use Illuminate\Console\Command;

class SimulateWorkerMovement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:simulate-movement {tenant_id} {user_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Simulate real-time worker movement by pushing coordinates to the sync service every 2 seconds.';

    /**
     * Execute the console command.
     */
    public function handle(SyncService $syncService): void
    {
        $tenantId = $this->argument('tenant_id');
        $userId = $this->argument('user_id');

        // Initialize tenant context
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error("Tenant with ID '{$tenantId}' not found.");
            return;
        }

        tenancy()->initialize($tenant);

        // Nairobi starting coordinates
        $lat = -1.2921;
        $lng = 36.8219;

        $this->info("Starting movement simulation for User #{$userId} on Tenant '{$tenantId}'...");

        for ($i = 1; $i <= 20; $i++) {
            // Apply a tiny random offset to simulate movement
            $lat += (rand(-5, 5) / 10000);
            $lng += (rand(-5, 5) / 10000);

            $payload = [
                'id' => 99000 + $i,
                'user_id' => (int) $userId,
                'location' => [
                    'latitude' => $lat,
                    'longitude' => $lng,
                ],
                'speed' => (float) (rand(10, 50) / 10),
                'recorded_at_mobile' => now()->toDateTimeString(),
                'version' => 1,
                'last_updated_at' => now()->toDateTimeString(),
            ];

            // Push payload directly through sync service
            $syncService->processPush(TrackingLog::class, [$payload]);

            $this->info("Iteration #{$i}: Pushed coordinates [Lat: {$lat}, Lng: {$lng}]");

            sleep(2);
        }

        $this->info("Simulation completed successfully.");
    }
}
