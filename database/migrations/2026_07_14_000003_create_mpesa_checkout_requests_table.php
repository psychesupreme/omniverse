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
        Schema::create('mpesa_checkout_requests', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id');
            $table->foreignId('subscription_plan_id')->constrained('subscription_plans')->cascadeOnDelete();
            $table->string('checkout_request_id')->unique();
            $table->string('merchant_request_id');
            $table->string('phone');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending'); // pending/success/failed
            $table->timestamps();

            // Foreign key referencing tenants string ID
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mpesa_checkout_requests');
    }
};
