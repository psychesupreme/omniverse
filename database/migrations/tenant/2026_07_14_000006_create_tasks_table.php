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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->unsignedBigInteger('assigned_user_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('pending'); // pending, accepted, in_progress, completed, cancelled
            $table->timestamp('scheduled_for');
            $table->timestamp('completed_at')->nullable();
            $table->string('evidence_photo_path')->nullable();
            $table->timestamps();

            // PostgreSQL cross-schema foreign key referencing the shared public.users table
            $table->foreign('assigned_user_id')->references('id')->on('public.users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
