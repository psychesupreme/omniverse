<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Geofence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GeofenceController extends Controller
{
    /**
     * Display a listing of active geofences.
     */
    public function index(): JsonResponse
    {
        $geofences = Geofence::where('is_active', true)->get();
        return response()->json($geofences);
    }

    /**
     * Store a newly created geofence in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                => ['required', 'string', 'max:255'],
            'description'         => ['nullable', 'string'],
            'coordinates'         => ['required', 'array', 'min:3'],
            'coordinates.*.lat'   => ['required', 'numeric'],
            'coordinates.*.lng'   => ['required', 'numeric'],
        ]);

        try {
            $geofence = Geofence::create([
                'name'            => $validated['name'],
                'description'     => $validated['description'] ?? null,
                'area'            => $validated['coordinates'],
                'is_active'       => true,
                'version'         => 1,
                'last_updated_at' => now(),
            ]);

            return response()->json([
                'message'  => 'Geofence created successfully.',
                'geofence' => $geofence,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Geofence creation failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while creating the geofence.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified geofence.
     */
    public function show(Geofence $geofence): JsonResponse
    {
        return response()->json($geofence);
    }

    /**
     * Remove the specified geofence from storage.
     */
    public function destroy(Geofence $geofence): JsonResponse
    {
        try {
            $geofence->delete();
            return response()->json(['message' => 'Geofence deleted successfully.']);
        } catch (\Exception $e) {
            Log::error('Geofence deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while deleting the geofence.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
