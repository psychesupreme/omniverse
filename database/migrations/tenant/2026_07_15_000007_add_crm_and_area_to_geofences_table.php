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
        Schema::table('geofences', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->boolean('is_active')->default(true)->after('description');
            $table->geography('area', 'polygon', 4326)->nullable()->after('is_active');

            // Spatial index on area polygon
            $table->spatialIndex('area');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('geofences', function (Blueprint $table) {
            $table->dropSpatialIndex(['area']);
            $table->dropColumn(['description', 'is_active', 'area']);
        });
    }
};
