<?php

namespace Tests\Feature\Api;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use Tests\TestCase;

class TenantRegistrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up to ensure fresh state without using database transactions
        if ($tenant = Tenant::find('acme-test')) {
            $tenant->delete();
        }
        SubscriptionPlan::where('slug', 'pro')->delete();
    }

    protected function tearDown(): void
    {
        if ($tenant = Tenant::find('acme-test')) {
            $tenant->delete();
        }
        SubscriptionPlan::where('slug', 'pro')->delete();

        parent::tearDown();
    }

    public function test_tenant_registration_successful()
    {
        // 1. Seed a plan
        $plan = SubscriptionPlan::create([
            'name'          => 'Pro',
            'slug'          => 'pro',
            'price_monthly' => 149.00,
            'price_annual'  => 1490.00,
            'max_users'     => 50,
            'max_outlets'   => 5000,
            'features'      => [],
        ]);

        // 2. Post registration payload
        $response = $this->postJson('/api/tenants/register', [
            'company_name'   => 'Acme Corporation',
            'domain'         => 'acme-test',
            'admin_name'     => 'John Doe',
            'admin_email'    => 'john@acme.com',
            'admin_password' => 'superpassword',
            'plan_id'        => $plan->id,
        ]);

        $response->assertStatus(201);
        $response->assertJsonStructure([
            'message',
            'url',
        ]);

        // 3. Assert tenant created in central DB
        $this->assertDatabaseHas('tenants', [
            'id'                   => 'acme-test',
            'subscription_plan_id' => $plan->id,
        ]);

        // 4. Assert domain created in central DB
        $this->assertDatabaseHas('domains', [
            'tenant_id' => 'acme-test',
            'domain'    => 'acme-test.localhost',
        ]);

        // 5. Assert user created inside tenant DB schema
        $tenant = Tenant::find('acme-test');
        $tenant->run(function () {
            $this->assertDatabaseHas('users', [
                'name'  => 'John Doe',
                'email' => 'john@acme.com',
            ]);
        });
    }

    public function test_tenant_registration_validation_fails()
    {
        $response = $this->postJson('/api/tenants/register', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'company_name',
            'domain',
            'admin_name',
            'admin_email',
            'admin_password',
            'plan_id',
        ]);
    }
}
