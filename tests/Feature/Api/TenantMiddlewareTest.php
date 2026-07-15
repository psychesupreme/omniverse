<?php

namespace Tests\Feature\Api;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantMiddlewareTest extends TestCase
{
    protected array $createdTenantIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up to ensure fresh state without using database transactions
        User::query()->delete();

        // Register dummy routes for testing middlewares
        Route::middleware(['web', \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class, 'tenant.feature:geofencing'])->get('/_test/feature', function () {
            return response()->json(['status' => 'ok']);
        });

        Route::middleware(['web', \Stancl\Tenancy\Middleware\InitializeTenancyByDomain::class, 'tenant.limit:users'])->get('/_test/limit', function () {
            return response()->json(['status' => 'ok']);
        });
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        // Clean up all dynamically created tenants
        foreach ($this->createdTenantIds as $id) {
            if ($tenant = Tenant::find($id)) {
                $tenant->delete();
            }
        }
        User::query()->delete();
        SubscriptionPlan::whereIn('slug', ['pro', 'basic'])->delete();

        parent::tearDown();
    }

    protected function createTenantWithPlan(string $id, array $planData): Tenant
    {
        $plan = SubscriptionPlan::create($planData);

        $tenant = Tenant::create([
            'id'                   => $id,
            'subscription_plan_id' => $plan->id,
            'status'               => 'active',
        ]);
        $tenant->domains()->create(['domain' => $id . '.localhost']);

        $this->createdTenantIds[] = $id;

        return $tenant;
    }

    public function test_feature_middleware_allows_access_when_enabled()
    {
        $tenant = $this->createTenantWithPlan('acme-test-1', [
            'name'          => 'Pro',
            'slug'          => 'pro',
            'price_monthly' => 149,
            'price_annual'  => 1490,
            'max_users'     => 50,
            'max_outlets'   => 5000,
            'features'      => ['geofencing' => true],
        ]);

        $response = $this->getJson('http://acme-test-1.localhost/_test/feature');
        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_feature_middleware_blocks_access_when_disabled()
    {
        $tenant = $this->createTenantWithPlan('acme-test-2', [
            'name'          => 'Basic',
            'slug'          => 'basic',
            'price_monthly' => 49,
            'price_annual'  => 490,
            'max_users'     => 10,
            'max_outlets'   => 500,
            'features'      => ['geofencing' => false],
        ]);

        $response = $this->getJson('http://acme-test-2.localhost/_test/feature');
        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
    }

    public function test_limit_middleware_allows_access_when_below_limit()
    {
        $tenant = $this->createTenantWithPlan('acme-test-3', [
            'name'          => 'Basic',
            'slug'          => 'basic',
            'price_monthly' => 49,
            'price_annual'  => 490,
            'max_users'     => 2,
            'max_outlets'   => 500,
            'features'      => [],
        ]);

        // Count is 0 initially (below limit of 2)
        $response = $this->getJson('http://acme-test-3.localhost/_test/limit');
        $response->assertStatus(200);

        // Create 1 user inside tenant schema
        $tenant->run(function () {
            User::create([
                'name'     => 'User 1',
                'email'    => 'user1@example.com',
                'password' => 'password',
            ]);
        });

        // Count is 1 (still below limit of 2)
        $response = $this->getJson('http://acme-test-3.localhost/_test/limit');
        $response->assertStatus(200);
    }

    public function test_limit_middleware_blocks_access_when_limit_reached()
    {
        $tenant = $this->createTenantWithPlan('acme-test-4', [
            'name'          => 'Basic',
            'slug'          => 'basic',
            'price_monthly' => 49,
            'price_annual'  => 490,
            'max_users'     => 1,
            'max_outlets'   => 500,
            'features'      => [],
        ]);

        // Create 1 user inside tenant schema (reaches limit of 1)
        $tenant->run(function () {
            User::create([
                'name'     => 'User 2',
                'email'    => 'user2@example.com',
                'password' => 'password',
            ]);
        });

        $response = $this->getJson('http://acme-test-4.localhost/_test/limit');
        $response->assertStatus(403);
        $response->assertJsonStructure(['message']);
    }
}
