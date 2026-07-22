<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Tenant Routes
|--------------------------------------------------------------------------
|
| Here you can register the tenant routes for your application.
| These routes are loaded by the TenantRouteServiceProvider.
|
| Feel free to customize them however you want. Good luck!
|
*/

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('/dashboard', \App\Http\Controllers\DashboardController::class)->name('dashboard');

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::get('/dispatch', [\App\Http\Controllers\DispatcherController::class, 'index'])->name('dispatch.index');
        Route::get('/dispatch/geofences', [\App\Http\Controllers\GeofenceWebController::class, 'index'])->name('dispatch.geofences');
        Route::get('/dispatch/products', [\App\Http\Controllers\ProductWebController::class, 'index'])->name('dispatch.products.index');
        Route::get('/dispatch/orders', [\App\Http\Controllers\OrderWebController::class, 'index'])->name('dispatch.orders.index');
        Route::get('/dispatch/timesheets', [\App\Http\Controllers\TimesheetWebController::class, 'index'])->name('dispatch.timesheets.index');
    });

    require __DIR__.'/auth.php';

    Broadcast::routes();
    require base_path('routes/channels.php');
});

Route::middleware([
    'api',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->prefix('api/v1')->group(function () {
    Route::post('/login', [App\Http\Controllers\Api\V1\AuthController::class, 'login']);
    Route::post('/mobile/login', [App\Http\Controllers\Api\V1\MobileAuthController::class, 'login']);
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
        Route::post('/sync/pull', [App\Http\Controllers\Api\V1\SyncController::class, 'pull']);
        Route::post('/sync/push', [App\Http\Controllers\Api\V1\SyncController::class, 'push']);

        // Dispatcher CRUD routes for task, geofence, product, order, and timesheet management
        Route::prefix('dispatch')->group(function () {
            Route::apiResource('tasks', App\Http\Controllers\Api\V1\TaskController::class);
            Route::apiResource('geofences', App\Http\Controllers\Api\V1\GeofenceController::class);
            Route::apiResource('products', App\Http\Controllers\Api\V1\ProductController::class);
            Route::apiResource('orders', App\Http\Controllers\Api\V1\OrderController::class)->except(['store', 'update', 'destroy']);
            Route::patch('orders/{order}/status', [App\Http\Controllers\Api\V1\OrderController::class, 'updateStatus']);
            Route::get('timesheets', [App\Http\Controllers\Api\V1\TimesheetController::class, 'index']);
            Route::post('timesheets/{timesheet}/override', [App\Http\Controllers\Api\V1\TimesheetController::class, 'manualOverride']);
            Route::post('routes/optimize', [App\Http\Controllers\Api\V1\RouteOptimizationController::class, 'optimize']);
        });

        // Worker routes for viewing and updating assigned tasks
        Route::prefix('mobile')->group(function () {
            Route::get('tasks', [App\Http\Controllers\Api\V1\WorkerTaskController::class, 'index']);
            Route::patch('tasks/{task}/status', [App\Http\Controllers\Api\V1\WorkerTaskController::class, 'updateStatus']);
            Route::get('timesheet/active', [App\Http\Controllers\Api\V1\TimesheetController::class, 'activeShift']);
        });
    });
});
