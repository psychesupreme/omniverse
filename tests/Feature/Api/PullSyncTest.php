<?php

namespace Tests\Feature\Api;

use App\Models\Outlet;
use App\Models\TrackingLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PullSyncTest extends TestCase
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
     * Test pulling all data on initial pull.
     */
    public function test_it_returns_all_data_on_initial_pull(): void
    {
        // Create 2 Outlets in tenant DB
        Outlet::create([
            'name' => 'Outlet A',
            'location' => ['latitude' => 37.7749, 'longitude' => -122.4194],
            'version' => 1,
            'last_updated_at' => now()->toDateTimeString(),
        ]);

        Outlet::create([
            'name' => 'Outlet B',
            'location' => ['latitude' => 34.0522, 'longitude' => -118.2437],
            'version' => 1,
            'last_updated_at' => now()->toDateTimeString(),
        ]);

        // Create 2 TrackingLogs in tenant DB
        TrackingLog::create([
            'user_id' => 1,
            'location' => ['latitude' => 37.7749, 'longitude' => -122.4194],
            'speed' => 10.5,
            'recorded_at_mobile' => now()->toDateTimeString(),
            'synced_at' => null,
            'version' => 1,
            'last_updated_at' => now()->toDateTimeString(),
        ]);

        TrackingLog::create([
            'user_id' => 2,
            'location' => ['latitude' => 34.0522, 'longitude' => -118.2437],
            'speed' => 25.0,
            'recorded_at_mobile' => now()->toDateTimeString(),
            'synced_at' => null,
            'version' => 1,
            'last_updated_at' => now()->toDateTimeString(),
        ]);

        // Send pull request
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('http://test.localhost/api/v1/sync/pull', [
            'collections' => ['outlets', 'tracking_logs'],
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'outlets',
                    'tracking_logs',
                ],
                'server_timestamp',
            ])
            ->assertJsonCount(2, 'data.outlets')
            ->assertJsonCount(2, 'data.tracking_logs');
    }

    /**
     * Test pulling only modified data after timestamp.
     */
    public function test_it_only_returns_data_modified_after_timestamp(): void
    {
        $yesterday = now()->subDay();
        $today = now();
        
        // Create 1 Outlet yesterday
        Outlet::create([
            'name' => 'Outlet Yesterday',
            'location' => ['latitude' => 37.7749, 'longitude' => -122.4194],
            'version' => 1,
            'last_updated_at' => $yesterday->toDateTimeString(),
        ]);

        // Create 1 Outlet today
        Outlet::create([
            'name' => 'Outlet Today',
            'location' => ['latitude' => 34.0522, 'longitude' => -118.2437],
            'version' => 1,
            'last_updated_at' => $today->toDateTimeString(),
        ]);

        // Yesterday night (e.g. 12 hours ago, which is after $yesterday but before $today)
        $lastSyncTimestamp = now()->subHours(12)->toDateTimeString();

        // Send pull request
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $this->token,
        ])->postJson('http://test.localhost/api/v1/sync/pull', [
            'last_sync_timestamp' => $lastSyncTimestamp,
            'collections' => ['outlets'],
        ]);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.outlets')
            ->assertJsonPath('data.outlets.0.name', 'Outlet Today');
    }
}
