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
        Schema::table('timesheets', function (Blueprint $table) {
            if (!Schema::hasColumn('timesheets', 'geofence_id')) {
                $table->foreignId('geofence_id')->nullable()->constrained('geofences')->nullOnDelete();
            }
            if (!Schema::hasColumn('timesheets', 'clock_in')) {
                $table->timestamp('clock_in')->nullable();
            }
            if (!Schema::hasColumn('timesheets', 'clock_out')) {
                $table->timestamp('clock_out')->nullable();
            }
            if (!Schema::hasColumn('timesheets', 'shift_duration_minutes')) {
                $table->integer('shift_duration_minutes')->nullable();
            }
            if (!Schema::hasColumn('timesheets', 'entry_event_id')) {
                $table->foreignId('entry_event_id')->nullable()->constrained('geofence_logs')->nullOnDelete();
            }
            if (!Schema::hasColumn('timesheets', 'exit_event_id')) {
                $table->foreignId('exit_event_id')->nullable()->constrained('geofence_logs')->nullOnDelete();
            }
            if (!Schema::hasColumn('timesheets', 'is_automated')) {
                $table->boolean('is_automated')->default(true);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('timesheets', function (Blueprint $table) {
            $table->dropForeign(['geofence_id']);
            $table->dropForeign(['entry_event_id']);
            $table->dropForeign(['exit_event_id']);
            $table->dropColumn([
                'geofence_id',
                'clock_in',
                'clock_out',
                'shift_duration_minutes',
                'entry_event_id',
                'exit_event_id',
                'is_automated',
            ]);
        });
    }
};
