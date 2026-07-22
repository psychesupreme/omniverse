<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TimesheetController extends Controller
{
    /**
     * Display a listing of timesheets with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Timesheet::with(['user', 'geofence']);

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->whereNull('clock_out');
            } elseif ($request->input('status') === 'completed') {
                $query->whereNotNull('clock_out');
            }
        }

        $timesheets = $query->latest('clock_in')->paginate(20);

        return response()->json($timesheets);
    }

    /**
     * Manually override or force close a timesheet record.
     */
    public function manualOverride(Request $request, Timesheet $timesheet): JsonResponse
    {
        $validated = $request->validate([
            'clock_in'  => ['nullable', 'date'],
            'clock_out' => ['nullable', 'date'],
            'status'    => ['nullable', 'string', 'in:active,completed,rejected'],
        ]);

        try {
            $clockIn = isset($validated['clock_in'])
                ? Carbon::parse($validated['clock_in'])
                : ($timesheet->clock_in ?? $timesheet->clock_in_time);

            $clockOut = isset($validated['clock_out'])
                ? Carbon::parse($validated['clock_out'])
                : $timesheet->clock_out;

            $durationMinutes = 0;
            if ($clockIn && $clockOut) {
                $durationMinutes = (int) $clockIn->diffInMinutes($clockOut);
            }

            $timesheet->update([
                'clock_in'               => $clockIn,
                'clock_in_time'          => $clockIn,
                'clock_out'              => $clockOut,
                'clock_out_time'         => $clockOut,
                'shift_duration_minutes' => $durationMinutes,
                'total_minutes'          => $durationMinutes,
                'is_automated'           => false, // Marked as manually overridden
                'status'                 => $validated['status'] ?? ($clockOut ? 'completed' : 'active'),
            ]);

            return response()->json([
                'message'   => 'Timesheet manually updated successfully.',
                'timesheet' => $timesheet->load(['user', 'geofence']),
            ]);

        } catch (\Exception $e) {
            Log::error('Timesheet manual override failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while updating the timesheet.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get active timesheet for the authenticated mobile worker.
     */
    public function activeShift(Request $request): JsonResponse
    {
        $user = $request->user();

        $activeTimesheet = Timesheet::with('geofence')
            ->where('user_id', $user->id)
            ->whereNull('clock_out')
            ->latest('clock_in')
            ->first();

        if (! $activeTimesheet) {
            return response()->json([
                'active' => false,
                'timesheet' => null,
            ]);
        }

        return response()->json([
            'active'    => true,
            'timesheet' => [
                'id'            => $activeTimesheet->id,
                'geofence_name' => $activeTimesheet->geofence?->name ?? 'Work Site',
                'clock_in'      => ($activeTimesheet->clock_in ?? $activeTimesheet->clock_in_time)?->toIso8601String(),
            ],
        ]);
    }
}
