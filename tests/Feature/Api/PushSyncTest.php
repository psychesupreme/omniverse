<?php

namespace Tests\Feature\Api;

use App\Models\Outlet;
use App\Models\TrackingLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PushSyncTest extends TestCase
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
        Outlet::withTrashed()->forceDelete();
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
     * Test creating new records pushed from mobile.
     */
    public function test_it_creates_new_records_pushed_from_mobile(): void
    {
        $outletId = 123;
        $trackingLogId = 456;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('http://test.localhost/api/v1/sync/push', [
            'client_timestamp' => now()->toDateTimeString(),
            'data' => [
                'outlets' => [
                    [
                        'id' => $outletId,
                        'name' => 'New Outlet Pushed',
                        'location' => ['latitude' => 37.7749, 'longitude' => -122.4194],
                        'version' => 1,
                        'last_updated_at' => now()->toDateTimeString(),
                    ]
                ],
                'tracking_logs' => [
                    [
                        'id' => $trackingLogId,
                        'user_id' => 1,
                        'location' => ['latitude' => 37.7749, 'longitude' => -122.4194],
                        'speed' => 12.5,
                        'recorded_at_mobile' => now()->toDateTimeString(),
                        'version' => 1,
                        'last_updated_at' => now()->toDateTimeString(),
                    ]
                ]
            ]
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Sync processed successfully.',
            ]);

        // Assert database has these records
        $this->assertTrue(Outlet::where('id', $outletId)->exists());
        $this->assertTrue(TrackingLog::where('id', $trackingLogId)->exists());

        $outlet = Outlet::find($outletId);
        $this->assertEquals('New Outlet Pushed', $outlet->name);
        $this->assertEquals(37.7749, $outlet->location['latitude']);
        $this->assertEquals(-122.4194, $outlet->location['longitude']);
    }

    /**
     * Test updating a record if mobile is newer.
     */
    public function test_it_updates_record_if_mobile_is_newer(): void
    {
        $outletId = 123;
        $yesterday = now()->subDay();
        $today = now();

        // Create Outlet on server set to yesterday
        Outlet::create([
            'id' => $outletId,
            'name' => 'Old Outlet Name',
            'location' => ['latitude' => 37.7749, 'longitude' => -122.4194],
            'version' => 1,
            'last_updated_at' => $yesterday->toDateTimeString(),
        ]);

        // Push new name set to today (newer)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('http://test.localhost/api/v1/sync/push', [
            'client_timestamp' => now()->toDateTimeString(),
            'data' => [
                'outlets' => [
                    [
                        'id' => $outletId,
                        'name' => 'New Outlet Name',
                        'location' => ['latitude' => 37.7749, 'longitude' => -122.4194],
                        'version' => 2,
                        'last_updated_at' => $today->toDateTimeString(),
                    ]
                ]
            ]
        ]);

        $response->assertStatus(200);

        // Assert server updated name
        $outlet = Outlet::find($outletId);
        $this->assertEquals('New Outlet Name', $outlet->name);
        $this->assertEquals(2, $outlet->version);
    }

    /**
     * Test rejecting update if server is newer (LWW).
     */
    public function test_it_rejects_update_if_server_is_newer_LWW(): void
    {
        $outletId = 123;
        $yesterday = now()->subDay();
        $today = now();

        // Create Outlet on server set to today
        Outlet::create([
            'id' => $outletId,
            'name' => 'Today Server Name',
            'location' => ['latitude' => 37.7749, 'longitude' => -122.4194],
            'version' => 2,
            'last_updated_at' => $today->toDateTimeString(),
        ]);

        // Push update set to yesterday (older)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('http://test.localhost/api/v1/sync/push', [
            'client_timestamp' => now()->toDateTimeString(),
            'data' => [
                'outlets' => [
                    [
                        'id' => $outletId,
                        'name' => 'Yesterday Mobile Name',
                        'location' => ['latitude' => 37.7749, 'longitude' => -122.4194],
                        'version' => 1,
                        'last_updated_at' => $yesterday->toDateTimeString(),
                    ]
                ]
            ]
        ]);

        $response->assertStatus(200);

        // Assert database name did NOT change
        $outlet = Outlet::find($outletId);
        $this->assertEquals('Today Server Name', $outlet->name);
        $this->assertEquals(2, $outlet->version);
    }

    /**
     * Test processing soft deletes.
     */
    public function test_it_processes_soft_deletes(): void
    {
        $outletId = 123;

        // Create Outlet in database
        $outlet = Outlet::create([
            'id' => $outletId,
            'name' => 'Active Outlet',
            'location' => ['latitude' => 37.7749, 'longitude' => -122.4194],
            'version' => 1,
            'last_updated_at' => now()->subDay()->toDateTimeString(),
        ]);

        $this->assertNull($outlet->deleted_at);

        // Send push payload with deleted_at populated and newer last_updated_at
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('http://test.localhost/api/v1/sync/push', [
            'client_timestamp' => now()->toDateTimeString(),
            'data' => [
                'outlets' => [
                    [
                        'id' => $outletId,
                        'name' => 'Active Outlet',
                        'location' => ['latitude' => 37.7749, 'longitude' => -122.4194],
                        'version' => 2,
                        'last_updated_at' => now()->toDateTimeString(),
                        'deleted_at' => now()->toDateTimeString(),
                    ]
                ]
            ]
        ]);

        $response->assertStatus(200);

        // Assert record is soft-deleted
        $this->assertTrue(Outlet::withTrashed()->where('id', $outletId)->exists());
        $this->assertTrue(Outlet::withTrashed()->find($outletId)->trashed());
    }
}
