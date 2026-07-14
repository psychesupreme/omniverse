<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterTenantRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class TenantRegistrationController extends Controller
{
    /**
     * Provision a new tenant and seed their initial administrator user.
     */
    public function register(RegisterTenantRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // 1. Create the tenant instance on the central database
            $tenant = Tenant::create([
                'id'                   => $validated['domain'],
                'subscription_plan_id' => $validated['plan_id'],
                'status'               => 'active',
            ]);

            // 2. Map the subdomains (both .localhost and .lvh.me for full DNS resolution support)
            $tenant->domains()->create([
                'domain' => $validated['domain'] . '.localhost',
            ]);
            $tenant->domains()->create([
                'domain' => $validated['domain'] . '.lvh.me',
            ]);

            // 3. Initialize tenant database context to seed their admin user in isolation
            $tenant->run(function () use ($validated) {
                \App\Models\User::create([
                    'name'     => $validated['admin_name'],
                    'email'    => $validated['admin_email'],
                    'password' => Hash::make($validated['admin_password']),
                ]);
            });

            DB::commit();

            return response()->json([
                'message' => 'Tenant registered and provisioned successfully.',
                'url'     => 'http://' . $validated['domain'] . '.lvh.me:8888',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Tenant onboarding failed: ' . $e->getMessage(), [
                'domain' => $validated['domain'] ?? null,
                'trace'  => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An error occurred during tenant registration.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
