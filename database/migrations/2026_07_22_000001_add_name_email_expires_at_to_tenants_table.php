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
            if (!Schema::hasColumn('tenants', 'name')) {
                $table->string('name')->nullable()->after('id');
            }
            if (!Schema::hasColumn('tenants', 'email')) {
                $table->string('email')->nullable()->after('name');
            }
            if (!Schema::hasColumn('tenants', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('subscription_ends_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(array_filter([
                Schema::hasColumn('tenants', 'name') ? 'name' : null,
                Schema::hasColumn('tenants', 'email') ? 'email' : null,
                Schema::hasColumn('tenants', 'expires_at') ? 'expires_at' : null,
            ]));
        });
    }
};
