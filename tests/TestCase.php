<?php

namespace Tests;

use App\Models\Tenant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;

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
        if ($this->tenant) {
            $this->tenant->delete();
            $this->tenant = null;
        }

        parent::tearDown();
    }
}
