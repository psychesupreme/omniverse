<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckTenantLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $resource): Response
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

        $limitAttribute = 'max_' . $resource;

        // Resolve current count based on resource type
        $currentCount = 0;
        if ($resource === 'users') {
            $currentCount = \App\Models\User::count();
        } elseif ($resource === 'outlets') {
            $currentCount = \App\Models\Outlet::count();
        }

        if ($tenant->hasExceededLimit($limitAttribute, $currentCount)) {
            return response()->json([
                'message' => "The limit for {$resource} on your current plan ({$plan->name}) has been reached. Please upgrade your plan.",
            ], 403);
        }

        return $next($request);
    }
}
