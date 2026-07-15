<?php

namespace Tests\Feature\Api;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SeedTestTenantCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Only delete the acme tenant, NOT the shared test tenant
        Tenant::where('id', 'acme')->delete();
        
        // Truncate subscription plans table on central
        SubscriptionPlan::query()->delete();

        // Seed central subscription plans
        $this->seed(SubscriptionPlanSeeder::class);
    }

    protected function tearDown(): void
    {
        Tenant::where('id', 'acme')->delete();
        SubscriptionPlan::query()->delete();

        parent::tearDown();
    }

    public function test_artisan_command_provisions_and_seeds_test_tenant_successfully()
    {
        // Act: Run seed-test-tenant artisan command
        $exitCode = Artisan::call('omniroute:seed-test-tenant');

        $this->assertEquals(0, $exitCode);

        // Assert: Central database verification
        $this->assertDatabaseHas('tenants', [
            'id' => 'acme',
        ]);

        $tenant = Tenant::find('acme');
        $this->assertNotNull($tenant);

        // Assert: Tenant-isolated database verification
        $tenant->run(function () {
            $this->assertDatabaseHas('users', ['email' => 'admin@acme.com']);
            $this->assertDatabaseHas('users', ['email' => 'worker1@acme.com']);
            $this->assertDatabaseHas('users', ['email' => 'worker2@acme.com']);

            $this->assertDatabaseHas('outlets', ['name' => 'Nairobi CBD Branch']);
            $this->assertDatabaseHas('outlets', ['name' => 'Westlands Branch']);
            $this->assertDatabaseHas('outlets', ['name' => 'Kilimani Branch']);

            $this->assertDatabaseHas('tasks', ['title' => 'Stock Audit CBD']);
            $this->assertDatabaseHas('tasks', ['title' => 'Merchandising Westlands']);
        });
    }
}
