<?php

namespace Tests\Feature\Api;

use App\Events\GeofenceAlertTriggered;
use App\Models\Geofence;
use App\Models\TrackingLog;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GeofenceTest extends TestCase
{
    /**
     * The Sanctum authentication token.
     */
    protected string $token;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up tables to ensure a fresh state for each test
        Geofence::withTrashed()->forceDelete();
        TrackingLog::withTrashed()->forceDelete();
        User::query()->delete();

        // Create a test user and generate a token
        $user = User::create([
            'name' => 'Sync Test User',
            'email' => 'syncuser@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->token = $user->createToken('test-token')->plainTextToken;
    }

    /**
     * Test triggers geofence alert when tracking log is inside boundary.
     */
    public function test_it_triggers_geofence_alert_when_tracking_log_is_inside_boundary(): void
    {
        Event::fake([GeofenceAlertTriggered::class]);

        // Create a Geofence with a square boundary (lng lat coordinate points)
        Geofence::create([
            'name' => 'Safe Zone',
            'boundary' => [
                ['lat' => 0, 'lng' => 0],
                ['lat' => 10, 'lng' => 0],
                ['lat' => 10, 'lng' => 10],
                ['lat' => 0, 'lng' => 10],
                ['lat' => 0, 'lng' => 0]
            ],
            'version' => 1,
            'last_updated_at' => now()->toDateTimeString(),
        ]);

        // Post a tracking log inside the geofence boundary (lat: 5, lng: 5)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('http://test.localhost/api/v1/sync/push', [
            'client_timestamp' => now()->toDateTimeString(),
            'data' => [
                'tracking_logs' => [
                    [
                        'id' => 777,
                        'user_id' => 1,
                        'location' => ['latitude' => 5.0, 'longitude' => 5.0],
                        'speed' => 5.5,
                        'recorded_at_mobile' => now()->toDateTimeString(),
                        'version' => 1,
                        'last_updated_at' => now()->toDateTimeString(),
                    ]
                ]
            ]
        ]);

        $response->assertStatus(200);

        Event::assertDispatched(GeofenceAlertTriggered::class);
    }

    /**
     * Test does not trigger alert when outside boundary.
     */
    public function test_it_does_not_trigger_alert_when_outside_boundary(): void
    {
        Event::fake([GeofenceAlertTriggered::class]);

        Geofence::create([
            'name' => 'Safe Zone',
            'boundary' => [
                ['lat' => 0, 'lng' => 0],
                ['lat' => 10, 'lng' => 0],
                ['lat' => 10, 'lng' => 10],
                ['lat' => 0, 'lng' => 10],
                ['lat' => 0, 'lng' => 0]
            ],
            'version' => 1,
            'last_updated_at' => now()->toDateTimeString(),
        ]);

        // Post a tracking log outside the geofence boundary (lat: 20, lng: 20)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('http://test.localhost/api/v1/sync/push', [
            'client_timestamp' => now()->toDateTimeString(),
            'data' => [
                'tracking_logs' => [
                    [
                        'id' => 888,
                        'user_id' => 1,
                        'location' => ['latitude' => 20.0, 'longitude' => 20.0],
                        'speed' => 5.5,
                        'recorded_at_mobile' => now()->toDateTimeString(),
                        'version' => 1,
                        'last_updated_at' => now()->toDateTimeString(),
                    ]
                ]
            ]
        ]);

        $response->assertStatus(200);

        Event::assertNotDispatched(GeofenceAlertTriggered::class);
    }
}
