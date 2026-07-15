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
        Schema::create('geofence_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('geofence_id');
            $table->unsignedBigInteger('user_id'); // Primary key of users table is bigint
            $table->string('event_type'); // 'entry', 'exit'
            $table->timestamp('occurred_at');
            $table->timestamps();

            // Foreign keys
            $table->foreign('geofence_id')->references('id')->on('geofences')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexing for faster lookups
            $table->index(['user_id', 'geofence_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('geofence_logs');
    }
};
