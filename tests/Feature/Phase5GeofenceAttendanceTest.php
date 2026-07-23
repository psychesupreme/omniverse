<?php

namespace Tests\Feature;

use App\Events\AttendanceUpdated;
use App\Events\WorkerEnteredGeofence;
use App\Events\WorkerExitedGeofence;
use App\Listeners\AutomateWorkerTimesheet;
use App\Models\Geofence;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\Timesheet;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase5GeofenceAttendanceTest extends TestCase
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
     * Test 1: Automated Clock-In on Geofence Entry.
     */
    public function test_1_automated_clock_in_on_geofence_entry(): void
    {
        Event::fake([AttendanceUpdated::class]);

        $slug = 'testacme51';
        $tenant = $this->provisionTenant($slug);

        $tenant->run(function () use ($slug, $tenant) {
            $worker = User::create([
                'name'     => 'Worker Shift 1',
                'email'    => "worker1_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);

            $geofence = Geofence::create([
                'name'            => 'Central Logistics Depot',
                'description'     => 'Primary Warehouse',
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

            $entryEvent = new WorkerEnteredGeofence($worker, $geofence, now());

            // Handle entry event via listener
            $listener = new AutomateWorkerTimesheet();
            $listener->handleWorkerEntered($entryEvent);

            // Assert Timesheet record created
            $timesheet = Timesheet::where('user_id', $worker->id)
                ->where('geofence_id', $geofence->id)
                ->whereNull('clock_out')
                ->first();

            $this->assertNotNull($timesheet);
            $this->assertTrue($timesheet->is_automated);
            $this->assertEquals('active', $timesheet->status);
            $this->assertNotNull($timesheet->clock_in);

            // Assert AttendanceUpdated event dispatched for Reverb broadcast
            Event::assertDispatched(AttendanceUpdated::class, function ($event) use ($tenant) {
                return $event->tenantId === $tenant->id && $event->eventType === 'clock_in';
            });
        });

        $this->cleanUpTenant($slug);
    }

    /**
     * Test 2: Automated Clock-Out & Duration Calculation on Geofence Exit.
     */
    public function test_2_automated_clock_out_and_duration_calculation_on_exit(): void
    {
        Event::fake([AttendanceUpdated::class]);

        $slug = 'testacme52';
        $tenant = $this->provisionTenant($slug);

        $tenant->run(function () use ($slug, $tenant) {
            $worker = User::create([
                'name'     => 'Worker Shift 2',
                'email'    => "worker2_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);

            $geofence = Geofence::create([
                'name'            => 'Eastside Factory Site',
                'description'     => 'Factory Floor',
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

            $listener = new AutomateWorkerTimesheet();

            // 1. Clock-in 3 hours ago (180 minutes)
            $clockInTime = now()->subHours(3);
            $entryEvent = new WorkerEnteredGeofence($worker, $geofence, $clockInTime);
            $listener->handleWorkerEntered($entryEvent);

            // 2. Trigger Exit Event now
            $clockOutTime = now();
            $exitEvent = new WorkerExitedGeofence($worker, $geofence, $clockOutTime);
            $listener->handleWorkerExited($exitEvent);

            // Assert Timesheet updated with clock_out and duration
            $timesheet = Timesheet::where('user_id', $worker->id)
                ->where('geofence_id', $geofence->id)
                ->first();

            $this->assertNotNull($timesheet);
            $this->assertNotNull($timesheet->clock_out);
            $this->assertEquals('completed', $timesheet->status);
            $this->assertGreaterThanOrEqual(179, $timesheet->shift_duration_minutes);

            // Assert AttendanceUpdated event dispatched for clock_out
            Event::assertDispatched(AttendanceUpdated::class, function ($event) use ($tenant) {
                return $event->tenantId === $tenant->id && $event->eventType === 'clock_out';
            });
        });

        $this->cleanUpTenant($slug);
    }

    /**
     * Test 3: Active Shift API Endpoint & Offline Sync Inclusion.
     */
    public function test_3_active_shift_api_endpoint_and_offline_sync(): void
    {
        $slug = 'testacme53';
        $tenant = $this->provisionTenant($slug);

        $tenant->run(function () use ($slug) {
            $worker = User::create([
                'name'     => 'Worker Active Shift',
                'email'    => "worker3_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);
            $token = $worker->createToken('active-token')->plainTextToken;

            $geofence = Geofence::create([
                'name'            => 'Westlands Distribution Center',
                'description'     => 'Distribution Hub',
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

            // Create open active timesheet
            Timesheet::create([
                'user_id'                => $worker->id,
                'geofence_id'            => $geofence->id,
                'date'                   => now()->toDateString(),
                'clock_in'               => now(),
                'clock_in_time'          => now(),
                'is_automated'           => true,
                'status'                 => 'active',
                'shift_duration_minutes' => 0,
            ]);

            // 1. GET /api/v1/mobile/timesheet/active
            $activeRes = $this->withHeaders([
                'Host'          => $slug . '.localhost',
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])->getJson("http://{$slug}.localhost/api/v1/mobile/timesheet/active");

            $activeRes->assertStatus(200)
                      ->assertJson([
                          'active' => true,
                          'timesheet' => [
                              'geofence_name' => 'Westlands Distribution Center',
                          ]
                      ]);

            // 2. POST /api/v1/sync/pull (Include collections)
            $pullRes = $this->withHeaders([
                'Host'          => $slug . '.localhost',
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])->postJson("http://{$slug}.localhost/api/v1/sync/pull", [
                'last_sync_timestamp' => null,
                'collections'         => ['outlets', 'products'],
            ]);

            $pullRes->assertStatus(200)
                    ->assertJsonPath('data.active_shift.geofence_name', 'Westlands Distribution Center');
        });

        $this->cleanUpTenant($slug);
    }
}
