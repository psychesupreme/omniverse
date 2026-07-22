<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Prevent tenant users or non-super-admins from accessing central super-admin routes
        if (! $user || ! $user->is_super_admin) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Forbidden. Super-Admin access required.'], 403);
            }

            abort(403, 'Unauthorized access to Central Super-Admin Portal.');
        }

        return $next($request);
    }
}
