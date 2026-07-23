<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\TrackingLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class Phase4BatchSyncLWWTest extends TestCase
{
    protected SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->plan = SubscriptionPlan::firstOrCreate(
            ['slug' => 'basic'],
            [
                'name'          => 'Basic',
                'price_monthly' => 49.00,
                'price_annual'  => 490.00,
                'max_users'     => 10,
                'max_outlets'   => 100,
                'features'      => ['crm' => true],
            ]
        );
    }

    protected function tearDown(): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    protected function cleanUpTenant(string $slug): void
    {
        if (tenancy()->initialized) {
            tenancy()->end();
        }

        if ($tenant = Tenant::find($slug)) {
            try {
                $tenant->delete();
            } catch (\Exception $e) {}
        }

        $schemaName = 'tenant' . str_replace('-', '', strtolower($slug));
        try {
            DB::statement("DROP SCHEMA IF EXISTS \"{$schemaName}\" CASCADE");
        } catch (\Exception $e) {}
    }

    protected function provisionTenant(string $slug): Tenant
    {
        $this->cleanUpTenant($slug);

        $payload = [
            'company_name'   => 'Acme Test Corp ' . $slug,
            'domain'         => $slug,
            'admin_name'     => 'Acme Admin',
            'admin_email'    => "admin_{$slug}@testacme.com",
            'admin_password' => 'password123',
            'plan_id'        => $this->plan->id,
        ];

        $response = $this->postJson('/api/tenants/register', $payload);
        $response->assertStatus(201);

        return Tenant::findOrFail($slug);
    }

    /**
     * Test 1: Batch Push Processing & Last-Write-Wins (LWW) Conflict Resolution.
     */
    public function test_1_batch_push_processing_and_lww_conflict_resolution(): void
    {
        $slug = 'testacme41';
        $tenant = $this->provisionTenant($slug);

        $tenant->run(function () use ($slug) {
            $user = User::create([
                'name'     => 'Sync Agent',
                'email'    => "agent_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);
            $token = $user->createToken('sync-token')->plainTextToken;

            $outletId = 888;
            $yesterday = now()->subDay();
            $today = now();

            // Create initial Outlet on server set to today
            Outlet::create([
                'id'              => $outletId,
                'name'            => 'Server Newer Name',
                'location'        => ['latitude' => -1.2800, 'longitude' => 36.8100],
                'version'         => 2,
                'last_updated_at' => $today->toDateTimeString(),
            ]);

            // Scenario 1: Push older timestamp (Yesterday) -> Should be ignored by LWW
            $responseOld = $this->withHeaders([
                'Host'          => $slug . '.localhost',
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])->postJson("http://{$slug}.localhost/api/v1/sync/push", [
                'client_timestamp' => now()->toDateTimeString(),
                'data' => [
                    'outlets' => [
                        [
                            'id'              => $outletId,
                            'name'            => 'Mobile Older Name',
                            'location'        => ['latitude' => -1.2800, 'longitude' => 36.8100],
                            'version'         => 1,
                            'last_updated_at' => $yesterday->toDateTimeString(),
                        ]
                    ]
                ]
            ]);

            $responseOld->assertStatus(200);

            $outlet = Outlet::find($outletId);
            $this->assertEquals('Server Newer Name', $outlet->name); // Name NOT updated

            // Scenario 2: Push newer timestamp (Tomorrow/Next Minute) -> Should overwrite server record by LWW
            $future = now()->addMinute();
            $responseNew = $this->withHeaders([
                'Host'          => $slug . '.localhost',
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])->postJson("http://{$slug}.localhost/api/v1/sync/push", [
                'client_timestamp' => now()->toDateTimeString(),
                'data' => [
                    'outlets' => [
                        [
                            'id'              => $outletId,
                            'name'            => 'Mobile Newer LWW Winner',
                            'location'        => ['latitude' => -1.2800, 'longitude' => 36.8100],
                            'version'         => 3,
                            'last_updated_at' => $future->toDateTimeString(),
                        ]
                    ]
                ]
            ]);

            $responseNew->assertStatus(200);

            $outletUpdated = Outlet::find($outletId);
            $this->assertEquals('Mobile Newer LWW Winner', $outletUpdated->name); // Overwritten by LWW
        });

        $this->cleanUpTenant($slug);
    }

    /**
     * Test 2: Base64 Evidence Photo Decoding & Tenant Disk Storage.
     */
    public function test_2_base64_evidence_photo_decoding_and_tenant_storage(): void
    {
        Storage::fake('public');

        $slug = 'testacme42';
        $tenant = $this->provisionTenant($slug);

        $tenant->run(function () use ($slug, $tenant) {
            $user = User::create([
                'name'     => 'Task Worker',
                'email'    => "worker_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);
            $token = $user->createToken('task-token')->plainTextToken;

            $outlet = Outlet::create([
                'id'              => 999,
                'name'            => 'Inspection Target Outlet',
                'location'        => ['latitude' => -1.2800, 'longitude' => 36.8100],
                'version'         => 1,
                'last_updated_at' => now()->toDateTimeString(),
            ]);

            // Create pending task on server
            $task = Task::create([
                'title'            => 'Inspect Solar Installation',
                'description'      => 'Verify panel angles',
                'outlet_id'        => $outlet->id,
                'scheduled_for'    => now(),
                'status'           => 'in_progress',
                'assigned_user_id' => $user->id,
            ]);

            // Sample 1x1 transparent red PNG in Base64
            $base64Image = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

            $response = $this->withHeaders([
                'Host'          => $slug . '.localhost',
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])->postJson("http://{$slug}.localhost/api/v1/sync/push", [
                'client_timestamp' => now()->toDateTimeString(),
                'data' => [
                    'tasks' => [
                        [
                            'id'               => $task->id,
                            'status'           => 'completed',
                            'completion_notes' => 'Panels installed at 15 degrees angle',
                            'last_updated_at'  => now()->toDateTimeString(),
                            'evidence_photos'  => [$base64Image],
                        ]
                    ]
                ]
            ]);

            $response->assertStatus(200);

            $updatedTask = Task::find($task->id);
            $this->assertEquals('completed', $updatedTask->status);
            $this->assertEquals('Panels installed at 15 degrees angle', $updatedTask->completion_notes);
            $this->assertNotEmpty($updatedTask->evidence_photo_path);

            // Assert file stored on tenant storage disk
            Storage::disk('public')->assertExists($updatedTask->evidence_photo_path);
        });

        $this->cleanUpTenant($slug);
    }

    /**
     * Test 3: Transactional Order Persistence & Product Stock Deduction.
     */
    public function test_3_transactional_order_persistence_and_stock_deduction(): void
    {
        $slug = 'testacme43';
        $tenant = $this->provisionTenant($slug);

        $tenant->run(function () use ($slug) {
            $user = User::create([
                'name'     => 'Sales Representative',
                'email'    => "sales_{$slug}@testacme.com",
                'password' => Hash::make('password123'),
            ]);
            $token = $user->createToken('sales-token')->plainTextToken;

            // Create initial product with stock = 100
            $product = Product::create([
                'sku'            => 'SOLAR-100W',
                'name'           => '100W Monocrystalline Solar Panel',
                'description'    => 'High efficiency panel',
                'unit_price'     => 120.00,
                'stock_quantity' => 100,
                'is_active'      => true,
            ]);

            $orderUuid = (string) Str::uuid();
            $orderNumber = 'ORD-' . time();

            $response = $this->withHeaders([
                'Host'          => $slug . '.localhost',
                'Authorization' => 'Bearer ' . $token,
                'Accept'        => 'application/json',
            ])->postJson("http://{$slug}.localhost/api/v1/sync/push", [
                'client_timestamp' => now()->toDateTimeString(),
                'data' => [
                    'orders' => [
                        [
                            'id'           => $orderUuid,
                            'order_number' => $orderNumber,
                            'outlet_id'    => null,
                            'total_amount' => 600.00,
                            'status'       => 'pending',
                            'notes'        => 'Bulk order for client',
                            'placed_at'    => now()->toDateTimeString(),
                            'items'        => [
                                [
                                    'product_id' => $product->id,
                                    'quantity'   => 5, // Ordering 5 panels
                                    'unit_price' => 120.00,
                                    'subtotal'   => 600.00,
                                ]
                            ]
                        ]
                    ]
                ]
            ]);

            $response->assertStatus(200)
                     ->assertJson(['status' => 'success']);

            // 1. Assert Order created in tenant.orders with expected mobile UUID
            $this->assertDatabaseHas('orders', [
                'id'           => $orderUuid,
                'order_number' => $orderNumber,
                'total_amount' => 600.00,
            ]);

            // 2. Assert OrderItem created in tenant.order_items
            $this->assertDatabaseHas('order_items', [
                'order_id'   => $orderUuid,
                'product_id' => $product->id,
                'quantity'   => 5,
                'unit_price' => 120.00,
            ]);

            // 3. Assert Product stock quantity decremented from 100 to 95
            $updatedProduct = Product::find($product->id);
            $this->assertEquals(95, $updatedProduct->stock_quantity);
        });

        $this->cleanUpTenant($slug);
    }
}
