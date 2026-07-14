<?php

namespace Tests\Feature\Api;

use App\Models\Outlet;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class TaskDispatchTest extends TestCase
{
    protected string $managerToken;
    protected string $workerToken;
    protected string $otherWorkerToken;

    protected User $manager;
    protected User $worker;
    protected User $otherWorker;
    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();

        // Clean up database tables before test execution (transactionless reset)
        Task::query()->delete();
        Outlet::withTrashed()->forceDelete();
        User::query()->delete();

        // 1. Create central/tenant users
        $this->manager = User::create([
            'name'     => 'Manager User',
            'email'    => 'manager@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->managerToken = $this->manager->createToken('manager-token')->plainTextToken;

        $this->worker = User::create([
            'name'     => 'Field Worker 1',
            'email'    => 'worker1@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->workerToken = $this->worker->createToken('worker-token')->plainTextToken;

        $this->otherWorker = User::create([
            'name'     => 'Field Worker 2',
            'email'    => 'worker2@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->otherWorkerToken = $this->otherWorker->createToken('other-worker-token')->plainTextToken;

        // 2. Create Outlet
        $this->outlet = Outlet::create([
            'name'            => 'Central Mall Outlet',
            'phone'           => '254700000000',
            'address'         => 'Nairobi Central, Kenya',
            'status'          => 'active',
            'location'        => ['latitude' => -1.2833, 'longitude' => 36.8167],
            'version'         => 1,
            'last_updated_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Task::query()->delete();
        Outlet::withTrashed()->forceDelete();
        User::query()->delete();

        parent::tearDown();
    }

    public function test_manager_can_crud_tasks()
    {
        // 1. Create a task (Store)
        $response = $this->withToken($this->managerToken)->postJson('http://test.localhost/api/v1/dispatch/tasks', [
            'outlet_id'        => $this->outlet->id,
            'assigned_user_id' => $this->worker->id,
            'title'            => 'Verify stock levels',
            'scheduled_for'    => now()->addDays(2)->toIso8601String(),
            'description'      => 'Count items on shelves A and B.',
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('task.title', 'Verify stock levels');
        $taskId = $response->json('task.id');

        // 2. View all tasks with filters (Index)
        $response = $this->withToken($this->managerToken)->getJson('http://test.localhost/api/v1/dispatch/tasks?status=pending');
        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');

        // 3. Update the task
        $response = $this->withToken($this->managerToken)->patchJson("http://test.localhost/api/v1/dispatch/tasks/{$taskId}", [
            'title' => 'Verify stock levels - UPDATED',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id'    => $taskId,
            'title' => 'Verify stock levels - UPDATED',
        ]);

        // 4. Delete the task (Destroy)
        $response = $this->withToken($this->managerToken)->deleteJson("http://test.localhost/api/v1/dispatch/tasks/{$taskId}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', ['id' => $taskId]);
    }

    public function test_worker_can_list_assigned_tasks_and_update_status()
    {
        // 1. Create tasks assigned to worker
        $task = Task::create([
            'outlet_id'        => $this->outlet->id,
            'assigned_user_id' => $this->worker->id,
            'title'            => 'Daily Visit 1',
            'scheduled_for'    => now()->addHour(),
            'status'           => 'pending',
        ]);

        // 2. Worker views tasks assigned to them (Index)
        $response = $this->withToken($this->workerToken)->getJson('http://test.localhost/api/v1/mobile/tasks');
        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.title', 'Daily Visit 1');

        // 3. Worker updates status to accepted
        $response = $this->withToken($this->workerToken)->patchJson("http://test.localhost/api/v1/mobile/tasks/{$task->id}/status", [
            'status' => 'accepted',
        ]);
        $response->assertStatus(200);
        $this->assertEquals('accepted', $task->fresh()->status);

        // 4. Worker updates status to completed
        $response = $this->withToken($this->workerToken)->patchJson("http://test.localhost/api/v1/mobile/tasks/{$task->id}/status", [
            'status' => 'completed',
        ]);
        $response->assertStatus(200);
        $task = $task->fresh();
        $this->assertEquals('completed', $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_worker_cannot_update_tasks_assigned_to_another_worker()
    {
        // 1. Create task assigned to worker 1
        $task = Task::create([
            'outlet_id'        => $this->outlet->id,
            'assigned_user_id' => $this->worker->id,
            'title'            => 'Daily Visit 1',
            'scheduled_for'    => now()->addHour(),
            'status'           => 'pending',
        ]);

        // 2. Worker 2 attempts to change status of task 1
        $response = $this->withToken($this->otherWorkerToken)->patchJson("http://test.localhost/api/v1/mobile/tasks/{$task->id}/status", [
            'status' => 'accepted',
        ]);

        $response->assertStatus(403);
        $this->assertEquals('pending', $task->fresh()->status);
    }
}
