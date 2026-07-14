<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Central\TenantRegistrationController;

/*
|--------------------------------------------------------------------------
| Central API Routes
|--------------------------------------------------------------------------
|
| Here you can register central API routes for the SaaS portal.
| These routes are loaded inside the central domain context.
|
*/

Route::post('/tenants/register', [TenantRegistrationController::class, 'register']);

// M-Pesa STK Push Payment Gateway Integration
Route::middleware('auth:sanctum')->post('/billing/mpesa/stk', [App\Http\Controllers\Central\MpesaPaymentController::class, 'initiateStkPush']);
Route::post('/billing/mpesa/callback', [App\Http\Controllers\Central\MpesaCallbackController::class, 'handleCallback']);
