<?php

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SuperAdminDashboardController extends Controller
{
    /**
     * Display central Super-Admin dashboard with aggregated SaaS metrics.
     */
    public function index(): Response
    {
        $totalTenants = Tenant::count();

        // Calculate Monthly Recurring Revenue (MRR) from active tenant subscription plans
        $mrr = Tenant::where('status', 'active')
            ->with('subscriptionPlan')
            ->get()
            ->sum(fn ($tenant) => (float) ($tenant->subscriptionPlan?->price_monthly ?? 0));

        // Total users registered centrally
        $activeWorkers = User::count();

        // Recent tenant registrations
        $recentTenants = Tenant::with(['domains', 'subscriptionPlan'])
            ->latest()
            ->take(5)
            ->get();

        return Inertia::render('Central/Dashboard', [
            'stats' => [
                'totalTenants'  => $totalTenants,
                'activeWorkers' => $activeWorkers,
                'mrr'           => round($mrr, 2),
            ],
            'recentTenants' => $recentTenants,
        ]);
    }

    /**
     * Display a listing of tenants with domain mappings, plan tier, and status.
     */
    public function tenants(): Response
    {
        $tenants = Tenant::with(['domains', 'subscriptionPlan'])
            ->latest()
            ->paginate(10);

        $plans = SubscriptionPlan::all();

        return Inertia::render('Central/Tenants/Index', [
            'tenants' => $tenants,
            'plans'   => $plans,
        ]);
    }

    /**
     * Toggle or update tenant status ('active' vs 'suspended').
     */
    public function updateStatus(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:active,suspended,pending'],
        ]);

        $tenant->update([
            'status' => $validated['status'],
        ]);

        return redirect()->back()->with('success', "Tenant {$tenant->id} status updated to {$validated['status']}.");
    }

    /**
     * Update tenant subscription plan.
     */
    public function updatePlan(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'subscription_plan_id' => ['required', 'exists:subscription_plans,id'],
        ]);

        $tenant->update([
            'subscription_plan_id' => $validated['subscription_plan_id'],
        ]);

        return redirect()->back()->with('success', "Tenant {$tenant->id} plan updated successfully.");
    }
}
