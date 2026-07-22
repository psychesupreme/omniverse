<?php

use App\Http\Controllers\Central\SuperAdminDashboardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes (Central SaaS Domain)
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    $host = request()->getHost();
    $isTenantDomain = \Stancl\Tenancy\Database\Models\Domain::where('domain', $host)->exists();
    if ($isTenantDomain) {
        return redirect()->to('/dashboard');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Central SaaS Super-Admin Portal Routes
Route::middleware(['web', 'auth', \App\Http\Middleware\EnsureSuperAdmin::class])->prefix('central')->group(function () {
    Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('central.dashboard');
    Route::get('/tenants', [SuperAdminDashboardController::class, 'tenants'])->name('central.tenants.index');
    Route::patch('/tenants/{tenant}/status', [SuperAdminDashboardController::class, 'updateStatus'])->name('central.tenants.update-status');
    Route::patch('/tenants/{tenant}/plan', [SuperAdminDashboardController::class, 'updatePlan'])->name('central.tenants.update-plan');
});
