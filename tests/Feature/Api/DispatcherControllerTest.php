<?php

namespace Tests\Feature\Api;

use App\Models\Outlet;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DispatcherControllerTest extends TestCase
{
    protected string $managerToken;
    protected User $manager;
    protected User $worker;
    protected Outlet $outlet;
    protected Task $task;

    protected function setUp(): void
    {
        parent::setUp();

        // Transactionless database tables reset
        Task::query()->delete();
        Outlet::withTrashed()->forceDelete();
        User::query()->delete();

        // 1. Create central/tenant users
        $this->manager = User::create([
            'name'     => 'Manager Dispatcher',
            'email'    => 'manager-dispatch@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->worker = User::create([
            'name'     => 'Mobile Agent 1',
            'email'    => 'mobile-agent1@example.com',
            'password' => bcrypt('password'),
        ]);

        // 2. Create Outlet
        $this->outlet = Outlet::create([
            'name'            => 'Pioneer Outlet',
            'phone'           => '254700000111',
            'address'         => 'Nairobi East, Kenya',
            'status'          => 'active',
            'location'        => ['latitude' => -1.2721, 'longitude' => 36.8142],
            'version'         => 1,
            'last_updated_at' => now(),
        ]);

        // 3. Create today's Task
        $this->task = Task::create([
            'outlet_id'        => $this->outlet->id,
            'assigned_user_id' => $this->worker->id,
            'title'            => 'Daily Stock Survey',
            'scheduled_for'    => now(),
            'status'           => 'pending',
        ]);
    }

    protected function tearDown(): void
    {
        Task::query()->delete();
        Outlet::withTrashed()->forceDelete();
        User::query()->delete();

        parent::tearDown();
    }

    public function test_dispatcher_dashboard_view_loads_successfully_with_inertia_props()
    {
        // Act: Request dispatch page as the authenticated manager user
        $response = $this->actingAs($this->manager)
            ->get('http://test.localhost/dispatch');

        $response->assertStatus(200);

        // Assert: Verify Inertia rendering and page props
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dispatch/Index')
            ->has('tenant_id')
            ->has('outlets', 1)
            ->has('workers', 2) // Includes both manager and worker users
            ->has('tasks', 1)
            ->where('outlets.0.name', 'Pioneer Outlet')
            ->where('tasks.0.title', 'Daily Stock Survey')
        );
    }
}
