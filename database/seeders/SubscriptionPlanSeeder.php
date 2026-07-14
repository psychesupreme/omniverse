<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Seed the subscription plans table with initial SaaS tiers.
     */
    public function run(): void
    {
        $plans = [
            [
                'name'          => 'Basic',
                'slug'          => 'basic',
                'price_monthly' => 49.00,
                'price_annual'  => 490.00,
                'max_users'     => 10,
                'max_outlets'   => 500,
                'features'      => [
                    'gps_tracking'      => true,
                    'offline_sync'      => true,
                    'geofencing'        => false,
                    'task_dispatching'  => false,
                    'sales_operations'  => false,
                    'hr_attendance'     => false,
                    'payroll'           => false,
                    'crm'               => false,
                    'analytics_basic'   => true,
                    'analytics_advanced'=> false,
                    'api_access'        => false,
                ],
            ],
            [
                'name'          => 'Pro',
                'slug'          => 'pro',
                'price_monthly' => 149.00,
                'price_annual'  => 1490.00,
                'max_users'     => 50,
                'max_outlets'   => 5000,
                'features'      => [
                    'gps_tracking'      => true,
                    'offline_sync'      => true,
                    'geofencing'        => true,
                    'task_dispatching'  => true,
                    'sales_operations'  => true,
                    'hr_attendance'     => true,
                    'payroll'           => false,
                    'crm'               => true,
                    'analytics_basic'   => true,
                    'analytics_advanced'=> true,
                    'api_access'        => false,
                ],
            ],
            [
                'name'          => 'Enterprise',
                'slug'          => 'enterprise',
                'price_monthly' => 499.00,
                'price_annual'  => 4990.00,
                'max_users'     => -1,    // unlimited
                'max_outlets'   => -1,    // unlimited
                'features'      => [
                    'gps_tracking'      => true,
                    'offline_sync'      => true,
                    'geofencing'        => true,
                    'task_dispatching'  => true,
                    'sales_operations'  => true,
                    'hr_attendance'     => true,
                    'payroll'           => true,
                    'crm'               => true,
                    'analytics_basic'   => true,
                    'analytics_advanced'=> true,
                    'api_access'        => true,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan
            );
        }
    }
}
