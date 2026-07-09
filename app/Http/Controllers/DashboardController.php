<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Geofence;
use App\Models\Outlet;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the tenant dashboard.
     */
    public function __invoke(): Response
    {
        $users = User::all(['id', 'name']);
        $geofences = Geofence::all(['id', 'name', 'boundary']);
        $outlets = Outlet::all(['id', 'name', 'location']);

        return Inertia::render('Dashboard', [
            'tenant_id' => tenant('id'),
            'users' => $users,
            'geofences' => $geofences,
            'outlets' => $outlets,
        ]);
    }
}
