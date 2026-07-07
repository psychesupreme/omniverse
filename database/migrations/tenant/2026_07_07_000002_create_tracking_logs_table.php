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
        Schema::create('tracking_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->geography('location', 'point', 4326);
            $table->double('speed');
            $table->timestamp('recorded_at_mobile');
            $table->timestamp('synced_at')->nullable();
            $table->integer('version');
            $table->timestamp('last_updated_at');
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->spatialIndex('location');
            $table->index('last_updated_at');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_logs');
    }
};
