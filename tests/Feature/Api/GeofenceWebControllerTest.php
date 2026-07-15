<?php

namespace Tests\Feature\Api;

use App\Models\Geofence;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class GeofenceWebControllerTest extends TestCase
{
    protected User $manager;
    protected Geofence $geofence;

    protected function setUp(): void
    {
        parent::setUp();

        // Transactionless cleanup
        Geofence::query()->delete();
        User::query()->delete();

        // Create manager user
        $this->manager = User::create([
            'name'     => 'Geofence Manager',
            'email'    => 'gf-manager@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create geofence
        $this->geofence = Geofence::create([
            'name'            => 'Restricted Industrial Zone',
            'description'     => 'Zone containing heavy machinery',
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
    }

    protected function tearDown(): void
    {
        Geofence::query()->delete();
        User::query()->delete();

        parent::tearDown();
    }

    public function test_geofence_management_view_loads_successfully_with_inertia_props()
    {
        // Act: Request geofence management page as the authenticated manager user
        $response = $this->actingAs($this->manager)
            ->get('http://test.localhost/dispatch/geofences');

        $response->assertStatus(200);

        // Assert: Verify Inertia rendering and page props
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Geofence/Index')
            ->has('geofences', 1)
            ->where('geofences.0.name', 'Restricted Industrial Zone')
            ->where('geofences.0.description', 'Zone containing heavy machinery')
        );
    }
}
