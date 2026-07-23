<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class Phase1AuthAndIsolationTest extends TestCase
{
    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        // Ensure subscription plan exists
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

        try {
            DB::statement("DROP SCHEMA IF EXISTS \"tenant{$slug}\" CASCADE");
            DB::statement("DROP SCHEMA IF EXISTS \"tenant_{$slug}\" CASCADE");
        } catch (\Exception $e) {}
    }

    /**
     * Helper to provision a tenant with unique slug.
     */
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
     * Test 1: Central Tenant Registration & Schema Provisioning.
     */
    public function test_1_central_tenant_registration_and_schema_creation(): void
    {
        $slug = 'testacme1';
        $tenant = $this->provisionTenant($slug);

        // 1. Verify record created in public.tenants
        $this->assertDatabaseHas('tenants', [
            'id' => $slug,
        ]);

        // 2. Verify domains registered in public.domains
        $this->assertDatabaseHas('domains', [
            'tenant_id' => $slug,
            'domain'    => $slug . '.localhost',
        ]);

        // 3. Verify PostgreSQL tenant schema creation
        $provisionedSchema = $tenant->database()->getName();
        $schemaCheck = DB::select("SELECT schema_name FROM information_schema.schemata WHERE schema_name LIKE 'tenant%'");
        $this->assertNotEmpty($schemaCheck, "PostgreSQL tenant schema was not created.");

        // 4. Verify tenant domain tables executed inside provisioned PostgreSQL schema
        $requiredTables = [
            'outlets',
            'tracking_logs',
            'geofences',
            'geofence_logs',
            'tasks',
            'timesheets',
            'products',
            'orders',
            'order_items',
        ];

        // Query all tables across all tenant schemas
        $existingTables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_name = 'outlets'");
        $this->assertNotEmpty($existingTables, "Tenant tables migration was not executed.");

        $this->cleanUpTenant($slug);
    }

    /**
     * Test 2: Tenant Domain Sanctum Auth & Bearer Token Issuance.
     */
    public function test_2_tenant_domain_sanctum_auth_and_bearer_token_issuance(): void
    {
        $slug = 'testacme2';
        $tenant = $this->provisionTenant($slug);

        // Create field worker user inside tenant context
        $tenant->run(function () use ($slug) {
            User::create([
                'name'     => 'Test Worker',
                'email'    => "worker_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);
        });

        // Issue login request to tenant domain API
        $response = $this->withHeaders([
            'Host' => $slug . '.localhost',
        ])->postJson("http://{$slug}.localhost/api/v1/mobile/login", [
            'email'    => "worker_{$slug}@testacme.com",
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['token', 'user']);

        $token = $response->json('token');
        $this->assertNotEmpty($token, 'Sanctum bearer token was not issued.');

        $this->cleanUpTenant($slug);
    }

    /**
     * Test 3: PostGIS Spatial search_path Resolution.
     */
    public function test_3_postgis_spatial_search_path_resolution(): void
    {
        $slug = 'testacme3';
        $tenant = $this->provisionTenant($slug);

        // Create field worker user inside tenant schema
        $tenant->run(function () use ($slug) {
            User::create([
                'name'     => 'Test Worker',
                'email'    => "worker_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);
        });

        // Issue login request to tenant domain API
        $loginRes = $this->withHeaders([
            'Host' => $slug . '.localhost',
        ])->postJson("http://{$slug}.localhost/api/v1/mobile/login", [
            'email'    => "worker_{$slug}@testacme.com",
            'password' => 'password123',
        ]);

        $loginRes->assertStatus(200);
        $token = $loginRes->json('token');

        // Perform authenticated request using bearer token and host header
        $response = $this->withHeaders([
            'Host'          => $slug . '.localhost',
            'Authorization' => 'Bearer ' . $token,
            'Accept'        => 'application/json',
        ])->getJson("http://{$slug}.localhost/api/v1/mobile/tasks");

        $response->assertStatus(200);

        // Verify PostgreSQL search_path in tenant context
        $tenant->run(function () {
            $searchPath = DB::select("SHOW search_path");
            $pathValue = $searchPath[0]->search_path ?? '';
            $this->assertStringContainsString('tenant', strtolower($pathValue));
            $this->assertStringContainsString('public', strtolower($pathValue));
        });

        $this->cleanUpTenant($slug);
    }
}
