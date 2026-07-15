<?php

namespace App\Console\Commands;

use App\Models\Outlet;
use App\Models\SubscriptionPlan;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Console\Command;

class SeedTestTenant extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'omniroute:seed-test-tenant';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seeds a test tenant (acme) with an admin, workers, outlets, and active tasks.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info("Resolving 'Pro' subscription plan...");
        $plan = SubscriptionPlan::where('slug', 'pro')->first();

        if (!$plan) {
            $this->error("Pro subscription plan not found. Please run the central database seeder first: php artisan db:seed");
            return 1;
        }

        $this->info("Checking/Creating tenant 'acme'...");
        $tenant = Tenant::find('acme');

        if (!$tenant) {
            $tenant = Tenant::create([
                'id'                   => 'acme',
                'subscription_plan_id' => $plan->id,
                'status'               => 'active',
            ]);
            $tenant->domains()->create(['domain' => 'acme.lvh.me']);
            $this->info("Tenant 'acme' and domain 'acme.lvh.me' created successfully.");
        } else {
            $this->info("Tenant 'acme' already exists.");
        }

        $this->info("Seeding tenant-isolated database...");
        
        $tenant->run(function () {
            // 1. Seed admin user
            $admin = User::updateOrCreate(
                ['email' => 'admin@acme.com'],
                [
                    'name'     => 'Acme Admin',
                    'password' => bcrypt('password'),
                ]
            );
            $this->info("Admin user 'admin@acme.com' set up.");

            // 2. Seed field workers
            $worker1 = User::updateOrCreate(
                ['email' => 'worker1@acme.com'],
                [
                    'name'     => 'Field Worker One',
                    'password' => bcrypt('password'),
                ]
            );

            $worker2 = User::updateOrCreate(
                ['email' => 'worker2@acme.com'],
                [
                    'name'     => 'Field Worker Two',
                    'password' => bcrypt('password'),
                ]
            );
            $this->info("Field workers 'worker1@acme.com' and 'worker2@acme.com' set up.");

            // 3. Seed Outlets in Nairobi
            $outletsData = [
                [
                    'name'     => 'Nairobi CBD Branch',
                    'phone'    => '254700000001',
                    'address'  => 'Kenyatta Avenue, Nairobi',
                    'status'   => 'active',
                    'location' => ['latitude' => -1.2833, 'longitude' => 36.8233],
                ],
                [
                    'name'     => 'Westlands Branch',
                    'phone'    => '254700000002',
                    'address'  => 'Mpaka Road, Westlands',
                    'status'   => 'active',
                    'location' => ['latitude' => -1.2633, 'longitude' => 36.8033],
                ],
                [
                    'name'     => 'Kilimani Branch',
                    'phone'    => '254700000003',
                    'address'  => 'Yaya Center, Kilimani',
                    'status'   => 'active',
                    'location' => ['latitude' => -1.2933, 'longitude' => 36.7893],
                ]
            ];

            $outlets = [];
            foreach ($outletsData as $data) {
                $outlets[] = Outlet::updateOrCreate(
                    ['name' => $data['name']],
                    [
                        'phone'           => $data['phone'],
                        'address'         => $data['address'],
                        'status'          => $data['status'],
                        'location'        => $data['location'],
                        'version'         => 1,
                        'last_updated_at' => now(),
                    ]
                );
            }
            $this->info("3 Nairobi outlets set up.");

            // 4. Seed today's Tasks
            Task::updateOrCreate(
                [
                    'title'         => 'Stock Audit CBD',
                    'scheduled_for' => now()->startOfDay()->addHours(9)->toDateTimeString(),
                ],
                [
                    'outlet_id'        => $outlets[0]->id,
                    'assigned_user_id' => $worker1->id,
                    'status'           => 'pending',
                ]
            );

            Task::updateOrCreate(
                [
                    'title'         => 'Merchandising Westlands',
                    'scheduled_for' => now()->startOfDay()->addHours(14)->toDateTimeString(),
                ],
                [
                    'outlet_id'        => $outlets[1]->id,
                    'assigned_user_id' => $worker2->id,
                    'status'           => 'pending',
                ]
            );
            $this->info("2 active tasks scheduled for today.");
        });

        $this->info("Tenant seeding process complete.");
        return 0;
    }
}
