<?php

namespace Tests\Feature\Api;

use App\Events\WorkerEnteredGeofence;
use App\Events\WorkerExitedGeofence;
use App\Models\Geofence;
use App\Models\GeofenceLog;
use App\Models\User;
use App\Services\GeofenceEvaluationService;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class GeofenceEvaluationServiceTest extends TestCase
{
    protected User $worker;
    protected Geofence $geofence;
    protected GeofenceEvaluationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // Transactionless cleanup
        GeofenceLog::query()->delete();
        Geofence::query()->delete();
        User::query()->delete();

        // Initialize service
        $this->service = new GeofenceEvaluationService();

        // Create worker user
        $this->worker = User::create([
            'name'     => 'Field Agent 1',
            'email'    => 'field-agent1@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create a square Geofence boundary around Nairobi (lng lat coordinate points)
        $this->geofence = Geofence::create([
            'name'            => 'Nairobi Industrial Zone',
            'description'     => 'Central industrial area',
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
        GeofenceLog::query()->delete();
        Geofence::query()->delete();
        User::query()->delete();

        parent::tearDown();
    }

    public function test_evaluator_triggers_entry_and_exit_events_correctly()
    {
        Event::fake([WorkerEnteredGeofence::class, WorkerExitedGeofence::class]);

        // Scenario 1: Worker starts outside the boundary (-1.3000, 36.8300)
        $this->service->evaluateLocation($this->worker, -1.3000, 36.8300, now()->toDateTimeString());
        
        $this->assertEquals(0, GeofenceLog::count());
        Event::assertNotDispatched(WorkerEnteredGeofence::class);
        Event::assertNotDispatched(WorkerExitedGeofence::class);

        // Scenario 2: Worker enters the boundary (-1.2850, 36.8150)
        $this->service->evaluateLocation($this->worker, -1.2850, 36.8150, now()->toDateTimeString());
        
        $this->assertEquals(1, GeofenceLog::count());
        $this->assertDatabaseHas('geofence_logs', [
            'user_id'     => $this->worker->id,
            'geofence_id' => $this->geofence->id,
            'event_type'  => 'entry',
        ]);
        Event::assertDispatched(WorkerEnteredGeofence::class, function ($event) {
            return $event->worker->id === $this->worker->id && $event->geofence->id === $this->geofence->id;
        });

        // Reset fake events for next step
        Event::fake([WorkerEnteredGeofence::class, WorkerExitedGeofence::class]);

        // Scenario 3: Worker stays inside the boundary (same coordinates)
        $this->service->evaluateLocation($this->worker, -1.2850, 36.8150, now()->toDateTimeString());
        
        $this->assertEquals(1, GeofenceLog::count()); // No new log
        Event::assertNotDispatched(WorkerEnteredGeofence::class);
        Event::assertNotDispatched(WorkerExitedGeofence::class);

        // Scenario 4: Worker exits the boundary (-1.3000, 36.8300)
        $this->service->evaluateLocation($this->worker, -1.3000, 36.8300, now()->toDateTimeString());

        $this->assertEquals(2, GeofenceLog::count());
        $this->assertDatabaseHas('geofence_logs', [
            'user_id'     => $this->worker->id,
            'geofence_id' => $this->geofence->id,
            'event_type'  => 'exit',
        ]);
        Event::assertDispatched(WorkerExitedGeofence::class, function ($event) {
            return $event->worker->id === $this->worker->id && $event->geofence->id === $this->geofence->id;
        });
    }
}
