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
        Schema::create('interaction_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('type'); // e.g. visit, call, note
            $table->text('notes');
            $table->timestamp('occurred_at');
            $table->timestamps();

            // PostgreSQL cross-schema foreign key mapping to the shared central users table
            $table->foreign('user_id')->references('id')->on('public.users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('interaction_logs');
    }
};
