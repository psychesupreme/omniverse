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
        Schema::table('outlets', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('name');
            $table->string('email')->nullable()->after('phone');
            $table->string('address')->nullable()->after('email');
            $table->string('status')->default('active')->after('address'); // active/inactive
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn(['phone', 'email', 'address', 'status']);
        });
    }
};
