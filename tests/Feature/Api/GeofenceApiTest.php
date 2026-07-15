<?php

namespace Tests\Feature\Api;

use App\Models\Geofence;
use App\Models\User;
use Tests\TestCase;

class GeofenceApiTest extends TestCase
{
    protected string $token;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();

        // Transactionless cleanup
        Geofence::query()->delete();
        User::query()->delete();

        // Create manager user for authentication
        $this->manager = User::create([
            'name'     => 'Manager User',
            'email'    => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->token = $this->manager->createToken('test-token')->plainTextToken;
    }

    protected function tearDown(): void
    {
        Geofence::query()->delete();
        User::query()->delete();

        parent::tearDown();
    }

    public function test_manager_can_crud_geofences_via_api()
    {
        // 1. Create a geofence (Store)
        $coordinates = [
            ['lat' => -1.2800, 'lng' => 36.8100],
            ['lat' => -1.2800, 'lng' => 36.8200],
            ['lat' => -1.2900, 'lng' => 36.8200],
            ['lat' => -1.2900, 'lng' => 36.8100],
            ['lat' => -1.2800, 'lng' => 36.8100], // Closed loop
        ];

        $response = $this->withToken($this->token)->postJson('http://test.localhost/api/v1/dispatch/geofences', [
            'name'        => 'Nairobi Central Geofence',
            'description' => 'Business district boundaries',
            'coordinates' => $coordinates,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('geofence.name', 'Nairobi Central Geofence');
        $geofenceId = $response->json('geofence.id');

        // 2. List active geofences (Index)
        $response = $this->withToken($this->token)->getJson('http://test.localhost/api/v1/dispatch/geofences');
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.name', 'Nairobi Central Geofence');

        // Verify coordinates parsed back to array of lat/lng
        $parsedCoords = $response->json('0.area');
        $this->assertIsArray($parsedCoords);
        $this->assertEquals(-1.2800, $parsedCoords[0]['lat']);
        $this->assertEquals(36.8100, $parsedCoords[0]['lng']);

        // 3. Delete geofence (Destroy)
        $response = $this->withToken($this->token)->deleteJson("http://test.localhost/api/v1/dispatch/geofences/{$geofenceId}");
        $response->assertStatus(200);
        $this->assertSoftDeleted('geofences', ['id' => $geofenceId]);
    }
}
