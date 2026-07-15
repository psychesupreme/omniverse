<?php

namespace App\Http\Controllers;

use App\Models\Geofence;
use Inertia\Inertia;
use Inertia\Response;

class GeofenceWebController extends Controller
{
    /**
     * Display the geofence management interface.
     */
    public function index(): Response
    {
        $geofences = Geofence::where('is_active', true)
            ->latest()
            ->get(['id', 'name', 'description', 'area', 'is_active']);

        return Inertia::render('Geofence/Index', [
            'geofences' => $geofences,
        ]);
    }
}
