<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase6RouteOptimizationTest extends TestCase
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
     * Test 1: PostGIS PgRouting Spatial Sequence Calculation & GeoJSON LineString.
     */
    public function test_1_postgis_pgrouting_spatial_sequence_calculation(): void
    {
        $slug = 'testacme61';
        $tenant = $this->provisionTenant($slug);

        $tenant->run(function () use ($slug) {
            $user = User::create([
                'name'     => 'Route Optimization Dispatcher',
                'email'    => "dispatcher_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);
            $token = $user->createToken('route-token')->plainTextToken;

            // Worker ID
            $workerId = $user->id;

            // Create Outlets at varying distances from start point (-1.2800, 36.8100)
            // Outlet Near: -1.2820, 36.8120 (~300m)
            $outletNear = Outlet::create([
                'id'              => 101,
                'name'            => 'Near Outlet A',
                'location'        => ['latitude' => -1.2820, 'longitude' => 36.8120],
                'version'         => 1,
                'last_updated_at' => now(),
            ]);

            // Outlet Far: -1.2950, 36.8250 (~2.2km)
            $outletFar = Outlet::create([
                'id'              => 102,
                'name'            => 'Far Outlet B',
                'location'        => ['latitude' => -1.2950, 'longitude' => 36.8250],
                'version'         => 1,
                'last_updated_at' => now(),
            ]);

            // Create pending tasks assigned to worker
            $taskFar = Task::create([
                'title'            => 'Task at Far Outlet B',
                'description'      => 'Far stopover',
                'outlet_id'        => $outletFar->id,
                'scheduled_for'    => now(),
                'status'           => 'pending',
                'assigned_user_id' => $workerId,
            ]);

            $taskNear = Task::create([
                'title'            => 'Task at Near Outlet A',
                'description'      => 'Near stopover',
                'outlet_id'        => $outletNear->id,
                'scheduled_for'    => now(),
                'status'           => 'pending',
                'assigned_user_id' => $workerId,
            ]);

            // POST /api/v1/dispatch/routes/optimize
            $response = $this->withHeaders([
                'Host'          => $slug . '.localhost',
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])->postJson("http://{$slug}.localhost/api/v1/dispatch/routes/optimize", [
                'worker_id'       => $workerId,
                'start_latitude'  => -1.2800,
                'start_longitude' => 36.8100,
            ]);

            $response->assertStatus(200)
                     ->assertJsonStructure([
                         'tasks',
                         'total_distance_km',
                         'estimated_time_mins',
                         'geojson',
                     ]);

            $tasks = $response->json('tasks');
            $this->assertCount(2, $tasks);

            // Assert nearest-neighbor sequence ordering (Near Outlet A must be Stop 1)
            $this->assertEquals($taskNear->id, $tasks[0]['task_id']);
            $this->assertEquals(1, $tasks[0]['sequence_order']);

            $this->assertEquals($taskFar->id, $tasks[1]['task_id']);
            $this->assertEquals(2, $tasks[1]['sequence_order']);

            // Assert distance calculations & GeoJSON payload
            $this->assertGreaterThan(0.0, $response->json('total_distance_km'));

            $geoJson = $response->json('geojson');
            $this->assertEquals('FeatureCollection', $geoJson['type']);
            $this->assertEquals('LineString', $geoJson['features'][0]['geometry']['type']);

            // Start coordinate + 2 task coordinates = 3 points in line string
            $lineCoords = $geoJson['features'][0]['geometry']['coordinates'];
            $this->assertCount(3, $lineCoords);
            $this->assertEquals(36.8100, $lineCoords[0][0]); // Start LNG
            $this->assertEquals(-1.2800, $lineCoords[0][1]); // Start LAT
        });

        $this->cleanUpTenant($slug);
    }
}
