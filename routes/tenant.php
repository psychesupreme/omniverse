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
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [App\Http\Controllers\Api\V1\AuthController::class, 'logout']);
        Route::post('/sync/pull', [App\Http\Controllers\Api\V1\SyncController::class, 'pull']);
        Route::post('/sync/push', [App\Http\Controllers\Api\V1\SyncController::class, 'push']);

        // Dispatcher CRUD routes for task management
        Route::prefix('dispatch')->group(function () {
            Route::apiResource('tasks', App\Http\Controllers\Api\V1\TaskController::class);
            Route::apiResource('geofences', App\Http\Controllers\Api\V1\GeofenceController::class);
        });

        // Worker routes for viewing and updating assigned tasks
        Route::prefix('mobile')->group(function () {
            Route::get('tasks', [App\Http\Controllers\Api\V1\WorkerTaskController::class, 'index']);
            Route::patch('tasks/{task}/status', [App\Http\Controllers\Api\V1\WorkerTaskController::class, 'updateStatus']);
        });
    });
});
