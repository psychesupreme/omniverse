<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantFeature
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = tenant();

        if (! $tenant) {
            return response()->json([
                'message' => 'No active tenant context detected.',
            ], 403);
        }

        $plan = $tenant->subscriptionPlan;

        if (! $plan) {
            return response()->json([
                'message' => 'No subscription plan associated with this account.',
            ], 403);
        }

        $features = $plan->features ?? [];

        if (! isset($features[$feature]) || ! $features[$feature]) {
            return response()->json([
                'message' => "Your subscription plan does not include the '{$feature}' feature. Please upgrade to unlock.",
            ], 403);
        }

        return $next($request);
    }
}
