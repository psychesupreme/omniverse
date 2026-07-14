<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('subscription_plans')
                  ->nullOnDelete();

            $table->string('stripe_customer_id')->nullable()->after('subscription_plan_id');
            $table->timestamp('trial_ends_at')->nullable()->after('stripe_customer_id');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
            $table->string('status')->default('active')->after('subscription_ends_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['subscription_plan_id']);
            $table->dropColumn([
                'subscription_plan_id',
                'stripe_customer_id',
                'trial_ends_at',
                'subscription_ends_at',
                'status',
            ]);
        });
    }
};
