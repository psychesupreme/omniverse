<?php

namespace App\Console\Commands;

use App\Models\Geofence;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class SeedTestTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omniroute:seed-test-tenant {slug=acmedemo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds an E2E manual testing tenant (acmedemo) with super-admin, dispatcher, worker, products, outlets, tasks, and geofences.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $slug = $this->argument('slug');

        $this->info("Resolving/creating 'Pro' subscription plan...");
        $plan = SubscriptionPlan::firstOrCreate(
            ['slug' => 'pro'],
            [
                'name'          => 'Pro Plan',
                'price_monthly' => 149.00,
                'price_annual'  => 1490.00,
                'max_users'     => 50,
                'max_outlets'   => 5000,
                'features'      => ['crm' => true, 'geofencing' => true, 'route_optimization' => true],
            ]
        );

        // 1. Seed Central Super-Admin User in central DB
        $superAdmin = User::updateOrCreate(
            ['email' => 'admin@omniroute.io'],
            [
                'name'           => 'OmniRoute Super Admin',
                'password'       => Hash::make('password'),
                'is_super_admin' => true,
            ]
        );
        $this->info("Super-Admin 'admin@omniroute.io' seeded in central DB.");

        // 2. Create/Reset Tenant
        $this->info("Checking/Creating tenant '{$slug}'...");
        $tenant = Tenant::find($slug);

        if (!$tenant) {
            $tenant = Tenant::create([
                'id'                   => $slug,
                'subscription_plan_id' => $plan->id,
                'status'               => 'active',
            ]);
            $tenant->domains()->create(['domain' => "{$slug}.localhost"]);
            $tenant->domains()->create(['domain' => "{$slug}.lvh.me"]);
            $this->info("Tenant '{$slug}' and domains '{$slug}.localhost' / '{$slug}.lvh.me' created.");
        } else {
            $this->info("Tenant '{$slug}' exists.");
        }

        // 3. Seed Tenant Context
        $this->info("Seeding tenant-isolated database context for '{$slug}'...");

        $tenant->run(function () use ($slug) {
            // Seed Dispatcher User
            $dispatcher = User::updateOrCreate(
                ['email' => "dispatcher@{$slug}.com"],
                [
                    'name'     => 'Acme Dispatcher',
                    'password' => Hash::make('password'),
                ]
            );
            $this->info("Dispatcher user 'dispatcher@{$slug}.com' set up.");

            // Seed Field Worker User
            $worker = User::updateOrCreate(
                ['email' => "worker@{$slug}.com"],
                [
                    'name'     => 'Acme Field Worker',
                    'password' => Hash::make('password'),
                ]
            );
            $this->info("Field worker user 'worker@{$slug}.com' set up.");

            // Seed 5 Products
            $productsData = [
                [
                    'sku'            => 'COFFEE-PREM-500G',
                    'name'           => 'Premium Coffee Beans 500g',
                    'description'    => 'Dark roast arabica beans',
                    'unit_price'     => 18.50,
                    'stock_quantity' => 150,
                ],
                [
                    'sku'            => 'TEA-BLACK-100BAG',
                    'name'           => 'Black Tea Leaves Box (100 Bags)',
                    'description'    => 'Fine Kenya CTC black tea',
                    'unit_price'     => 8.20,
                    'stock_quantity' => 200,
                ],
                [
                    'sku'            => 'SYRUP-CARAMEL-1L',
                    'name'           => 'Caramel Flavored Syrup 1L',
                    'description'    => 'Gourmet beverage syrup',
                    'unit_price'     => 12.00,
                    'stock_quantity' => 80,
                ],
                [
                    'sku'            => 'SYRUP-VANILLA-1L',
                    'name'           => 'Vanilla Flavored Syrup 1L',
                    'description'    => 'Classic vanilla flavoring',
                    'unit_price'     => 12.00,
                    'stock_quantity' => 95,
                ],
                [
                    'sku'            => 'PODS-ESPRESSO-50',
                    'name'           => 'Espresso Pods Pack (50 Pods)',
                    'description'    => 'Compatible espresso capsules',
                    'unit_price'     => 22.00,
                    'stock_quantity' => 110,
                ],
            ];

            foreach ($productsData as $pData) {
                Product::updateOrCreate(
                    ['sku' => $pData['sku']],
                    [
                        'name'           => $pData['name'],
                        'description'    => $pData['description'],
                        'unit_price'     => $pData['unit_price'],
                        'stock_quantity' => $pData['stock_quantity'],
                        'is_active'      => true,
                    ]
                );
            }
            $this->info("5 Products seeded into catalogue.");

            // Seed 3 Outlets around Nairobi
            $outletsData = [
                [
                    'id'       => 101,
                    'name'     => 'Nairobi CBD Main Outlet',
                    'phone'    => '254700000101',
                    'address'  => 'Kenyatta Avenue, Nairobi',
                    'location' => ['latitude' => -1.2833, 'longitude' => 36.8233],
                ],
                [
                    'id'       => 102,
                    'name'     => 'Westlands Hub Outlet',
                    'phone'    => '254700000102',
                    'address'  => 'Mpaka Road, Westlands, Nairobi',
                    'location' => ['latitude' => -1.2633, 'longitude' => 36.8033],
                ],
                [
                    'id'       => 103,
                    'name'     => 'Kilimani Junction Outlet',
                    'phone'    => '254700000103',
                    'address'  => 'Yaya Center, Kilimani, Nairobi',
                    'location' => ['latitude' => -1.2933, 'longitude' => 36.7893],
                ],
            ];

            $outlets = [];
            foreach ($outletsData as $oData) {
                $outlets[] = Outlet::updateOrCreate(
                    ['id' => $oData['id']],
                    [
                        'name'            => $oData['name'],
                        'phone'           => $oData['phone'],
                        'address'         => $oData['address'],
                        'status'          => 'active',
                        'location'        => $oData['location'],
                        'version'         => 1,
                        'last_updated_at' => now(),
                    ]
                );
            }
            $this->info("3 Outlets seeded in Nairobi area.");

            // Seed 3 Pending Tasks assigned to worker
            $tasksData = [
                [
                    'title'         => 'Morning Inventory Check - CBD',
                    'description'   => 'Audit coffee bean stock levels and verify syrup bottles',
                    'outlet_id'     => $outlets[0]->id,
                    'scheduled_for' => now()->startOfDay()->addHours(9)->toDateTimeString(),
                ],
                [
                    'title'         => 'Merchandising & Display Setup - Westlands',
                    'description'   => 'Set up new espresso pod promo display at entrance',
                    'outlet_id'     => $outlets[1]->id,
                    'scheduled_for' => now()->startOfDay()->addHours(11)->toDateTimeString(),
                ],
                [
                    'title'         => 'Afternoon Delivery & Re-Stock - Kilimani',
                    'description'   => 'Deliver 20 boxes of tea leaves and inspect storage area',
                    'outlet_id'     => $outlets[2]->id,
                    'scheduled_for' => now()->startOfDay()->addHours(14)->toDateTimeString(),
                ],
            ];

            foreach ($tasksData as $tData) {
                Task::updateOrCreate(
                    ['title' => $tData['title']],
                    [
                        'description'      => $tData['description'],
                        'outlet_id'        => $tData['outlet_id'],
                        'assigned_user_id' => $worker->id,
                        'status'           => 'pending',
                        'scheduled_for'    => $tData['scheduled_for'],
                    ]
                );
            }
            $this->info("3 Pending Tasks assigned to worker.");

            // Seed 1 Site Geofence Polygon around Nairobi CBD Outlet
            Geofence::updateOrCreate(
                ['name' => 'CBD Main Outlet Geofence Perimeter'],
                [
                    'description' => 'Geofence boundary surrounding Kenyatta Avenue CBD outlet',
                    'area'        => [
                        ['lat' => -1.2800, 'lng' => 36.8200],
                        ['lat' => -1.2800, 'lng' => 36.8300],
                        ['lat' => -1.2900, 'lng' => 36.8300],
                        ['lat' => -1.2900, 'lng' => 36.8200],
                        ['lat' => -1.2800, 'lng' => 36.8200],
                    ],
                    'is_active'       => true,
                    'version'         => 1,
                    'last_updated_at' => now(),
                ]
            );
            $this->info("1 Site Geofence polygon seeded around CBD outlet.");
        });

        $this->info("--------------------------------------------------");
        $this->info("E2E Manual Testing Environment '{$slug}' Ready!");
        $this->info("Access URLs:");
        $this->info("  - Tenant Web Portal: http://{$slug}.localhost:8888");
        $this->info("  - Central Super-Admin: http://localhost:8888/central/dashboard");
        $this->info("Credentials:");
        $this->info("  - Super Admin: admin@omniroute.io / password");
        $this->info("  - Dispatcher:  dispatcher@{$slug}.com / password");
        $this->info("  - Field Worker: worker@{$slug}.com / password");
        $this->info("--------------------------------------------------");

        return 0;
    }
}
