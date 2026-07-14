<?php

namespace Tests\Feature\Api;

use App\Models\InteractionLog;
use App\Models\Outlet;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class CrmTaskTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Clean up tables to ensure fresh state inside the initialized tenant connection
        Task::query()->delete();
        InteractionLog::query()->delete();
        Outlet::withTrashed()->forceDelete();
        User::query()->delete();
    }

    protected function tearDown(): void
    {
        Task::query()->delete();
        InteractionLog::query()->delete();
        Outlet::withTrashed()->forceDelete();
        User::query()->delete();

        parent::tearDown();
    }

    public function test_crm_and_tasks_tables_can_be_queried_and_interacted_with()
    {
        // 1. Create a user inside the public schema (linked by foreign keys)
        $user = User::create([
            'name'     => 'Field Worker',
            'email'    => 'worker@example.com',
            'password' => bcrypt('password'),
        ]);

        // 2. Create an Outlet inside the tenant context
        $outlet = Outlet::create([
            'name'            => 'Westlands Outlet',
            'phone'           => '254711223344',
            'email'           => 'westlands@example.com',
            'address'         => 'Westlands Mall, Nairobi',
            'status'          => 'active',
            'location'        => ['latitude' => -1.2612, 'longitude' => 36.8012],
            'version'         => 1,
            'last_updated_at' => now(),
        ]);

        $this->assertDatabaseHas('outlets', [
            'id'      => $outlet->id,
            'name'    => 'Westlands Outlet',
            'phone'   => '254711223344',
            'address' => 'Westlands Mall, Nairobi',
            'status'  => 'active',
        ]);

        // 3. Log an interaction
        $log = InteractionLog::create([
            'outlet_id'   => $outlet->id,
            'user_id'     => $user->id,
            'type'        => 'visit',
            'notes'       => 'Discussed new order terms.',
            'occurred_at' => now(),
        ]);

        $this->assertDatabaseHas('interaction_logs', [
            'id'        => $log->id,
            'outlet_id' => $outlet->id,
            'user_id'   => $user->id,
            'type'      => 'visit',
            'notes'     => 'Discussed new order terms.',
        ]);

        // 4. Create and dispatch a task
        $task = Task::create([
            'outlet_id'        => $outlet->id,
            'assigned_user_id' => $user->id,
            'title'            => 'Deliver Product Package',
            'description'      => 'Deliver 5 packages of product A.',
            'status'           => 'pending',
            'scheduled_for'    => now()->addDay(),
        ]);

        $this->assertDatabaseHas('tasks', [
            'id'               => $task->id,
            'outlet_id'        => $outlet->id,
            'assigned_user_id' => $user->id,
            'title'            => 'Deliver Product Package',
            'status'           => 'pending',
        ]);

        // 5. Test Eloquent relationships
        $this->assertCount(1, $outlet->interactionLogs);
        $this->assertCount(1, $outlet->tasks);
        $this->assertCount(1, $user->interactionLogs);
        $this->assertCount(1, $user->tasks);

        $this->assertEquals($outlet->id, $task->outlet->id);
        $this->assertEquals($user->id, $task->assignedUser->id);
        $this->assertEquals($outlet->id, $log->outlet->id);
        $this->assertEquals($user->id, $log->user->id);
    }
}
