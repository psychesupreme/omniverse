<?php

namespace Tests\Feature;

use App\Events\SyncProcessed;
use App\Events\WorkerEnteredGeofence;
use App\Events\WorkerExitedGeofence;
use App\Models\Geofence;
use App\Models\GeofenceLog;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TrackingLog;
use App\Models\User;
use App\Services\GeofenceEvaluationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase2DispatcherAndTelemetryTest extends TestCase
{
    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->plan = SubscriptionPlan::firstOrCreate(
            ['slug' => 'basic'],
            [
                'name'          => 'Basic',
                'price_monthly' => 49.00,
                'price_annual'  => 490.00,
                'max_users'     => 10,
                'max_outlets'   => 100,
                'features'      => ['crm' => true],
            ]
        );
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    protected function cleanUpTenant(string $slug): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        if ($tenant = Tenant::find($slug)) {
            try {
                $tenant->delete();
            } catch (\Exception $e) {}
        }

        $schemaName = 'tenant' . str_replace('-', '', strtolower($slug));
        try {
            DB::statement("DROP SCHEMA IF EXISTS \"{$schemaName}\" CASCADE");
        } catch (\Exception $e) {}
    }

    protected function provisionTenant(string $slug): Tenant
    {
        $this->cleanUpTenant($slug);

        $payload = [
            'company_name'   => 'Acme Test Corp ' . $slug,
            'domain'         => $slug,
            'admin_name'     => 'Acme Admin',
            'admin_email'    => "admin_{$slug}@testacme.com",
            'admin_password' => 'password123',
            'plan_id'        => $this->plan->id,
        ];

        $response = $this->postJson('/api/tenants/register', $payload);
        $response->assertStatus(201);

        return Tenant::findOrFail($slug);
    }

    /**
     * Test 1: Geofence Polygon Management (Leaflet.Draw Backend).
     */
    public function test_1_geofence_polygon_management_and_postgis_storage(): void
    {
        $slug = 'testacme21';
        $tenant = $this->provisionTenant($slug);

        // Create manager user inside tenant
        $tenant->run(function () use ($slug) {
            $user = User::create([
                'name'     => 'Dispatcher Manager',
                'email'    => "manager_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);
            $token = $user->createToken('test-token')->plainTextToken;

            // Coordinates defining a polygon around Nairobi Industrial Area
            $coordinates = [
                ['lat' => -1.2800, 'lng' => 36.8100],
                ['lat' => -1.2800, 'lng' => 36.8200],
                ['lat' => -1.2900, 'lng' => 36.8200],
                ['lat' => -1.2900, 'lng' => 36.8100],
                ['lat' => -1.2800, 'lng' => 36.8100],
            ];

            $response = $this->withHeaders([
                'Host'          => $slug . '.localhost',
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])->postJson("http://{$slug}.localhost/api/v1/dispatch/geofences", [
                'name'        => 'Nairobi Industrial Zone',
                'description' => 'Primary Depot Area',
                'coordinates' => $coordinates,
            ]);

            $response->assertStatus(201)
                     ->assertJsonPath('geofence.name', 'Nairobi Industrial Zone');

            $geofenceId = $response->json('geofence.id');
            $this->assertNotEmpty($geofenceId);

            // Assert stored in tenant geofences table
            $geofence = Geofence::find($geofenceId);
            $this->assertNotNull($geofence);
            $this->assertEquals('Nairobi Industrial Zone', $geofence->name);
            $this->assertNotNull($geofence->boundary);
        });

        $this->cleanUpTenant($slug);
    }

    /**
     * Test 2: GPS Location Ingestion & Reverb WebSocket Broadcasting.
     */
    public function test_2_gps_location_ingestion_and_reverb_broadcasting(): void
    {
        Event::fake([SyncProcessed::class]);

        $slug = 'testacme22';
        $tenant = $this->provisionTenant($slug);

        $tenant->run(function () use ($slug, $tenant) {
            $user = User::create([
                'name'     => 'Mobile Agent',
                'email'    => "agent_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);
            $token = $user->createToken('mobile-token')->plainTextToken;

            $trackingId = (string) Str::uuid();

            $response = $this->withHeaders([
                'Host'          => $slug . '.localhost',
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])->postJson("http://{$slug}.localhost/api/v1/sync/push", [
                'client_timestamp' => now()->toDateTimeString(),
                'data' => [
                    'tracking_logs' => [
                        [
                            'id'                 => $trackingId,
                            'user_id'            => $user->id,
                            'location'           => ['latitude' => -1.2850, 'longitude' => 36.8150],
                            'speed'              => 15.4,
                            'recorded_at_mobile' => now()->toDateTimeString(),
                            'version'            => 1,
                            'last_updated_at'    => now()->toDateTimeString(),
                        ]
                    ]
                ]
            ]);

            $response->assertStatus(200)
                     ->assertJson(['status' => 'success']);

            // Assert saved in tracking_logs
            $this->assertTrue(TrackingLog::where('id', $trackingId)->exists());

            // Assert SyncProcessed broadcast event dispatched
            Event::assertDispatched(SyncProcessed::class, function ($event) use ($tenant) {
                return $event->tenantId === $tenant->id;
            });
        });

        $this->cleanUpTenant($slug);
    }

    /**
     * Test 3: Point-in-Polygon Geofence Event Triggering.
     */
    public function test_3_point_in_polygon_geofence_event_triggering(): void
    {
        Event::fake([WorkerEnteredGeofence::class, WorkerExitedGeofence::class]);

        $slug = 'testacme23';
        $tenant = $this->provisionTenant($slug);

        $tenant->run(function () use ($slug) {
            $worker = User::create([
                'name'     => 'Field Agent Geofence',
                'email'    => "agent_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);

            // Create square geofence
            $geofence = Geofence::create([
                'name'            => 'Depot Perimeter',
                'description'     => 'Depot Area',
                'area'            => [
                    ['lat' => -1.2800, 'lng' => 36.8100],
                    ['lat' => -1.2800, 'lng' => 36.8200],
                    ['lat' => -1.2900, 'lng' => 36.8200],
                    ['lat' => -1.2900, 'lng' => 36.8100],
                    ['lat' => -1.2800, 'lng' => 36.8100],
                ],
                'is_active'       => true,
                'version'         => 1,
                'last_updated_at' => now(),
            ]);

            $evaluator = new GeofenceEvaluationService();

            // Evaluate location inside boundary (-1.2850, 36.8150)
            $evaluator->evaluateLocation($worker, -1.2850, 36.8150, now()->toDateTimeString());

            // Assert entry logged in geofence_logs
            $this->assertDatabaseHas('geofence_logs', [
                'user_id'     => $worker->id,
                'geofence_id' => $geofence->id,
                'event_type'  => 'entry',
            ]);

            // Assert WorkerEnteredGeofence event dispatched for Reverb broadcast
            Event::assertDispatched(WorkerEnteredGeofence::class, function ($event) use ($worker, $geofence) {
                return $event->worker->id === $worker->id && $event->geofence->id === $geofence->id;
            });
        });

        $this->cleanUpTenant($slug);
    }
}
