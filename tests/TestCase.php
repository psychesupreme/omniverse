<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Has the central database been migrated?
     *
     * @var bool
     */
    protected static bool $migrated = false;

    /**
     * The tenant instance.
     *
     * @var Tenant|null
     */
    protected ?Tenant $tenant = null;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Migrate central database once per test suite run
        if (!static::$migrated) {
            Artisan::call('migrate:fresh');
            
            // Clean up any legacy tenant database schemas to ensure a clean slate
            try {
                DB::statement('DROP SCHEMA IF EXISTS tenanttest CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS "tenantacme-test-1" CASCADE');
                DB::statement('DROP SCHEMA IF EXISTS tenantacme CASCADE');
            } catch (\Exception $e) {
                // Silence schema drops if they don't exist
            }

            static::$migrated = true;
        }

        // Initialize tenant context
        $this->setUpTenant();
    }

    /**
     * Initialize a test tenant.
     */
    protected function setUpTenant(): void
    {
        $tenantId = 'test';
        $this->tenant = Tenant::find($tenantId);

        if (!$this->tenant) {
            $this->tenant = Tenant::create(['id' => $tenantId]);
            $this->tenant->domains()->create(['domain' => 'test.localhost']);
        }

        tenancy()->initialize($this->tenant);
    }

    /**
     * Tear down the test environment.
     */
    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->tenant = null;

        parent::tearDown();
    }
}
