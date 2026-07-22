<?php

namespace App\Listeners;

use App\Events\AttendanceUpdated;
use App\Events\WorkerEnteredGeofence;
use App\Events\WorkerExitedGeofence;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class AutomateWorkerTimesheet
{
    /**
     * Handle worker entry geofence event (Clock-In).
     */
    public function handleWorkerEntered(WorkerEnteredGeofence $event): void
    {
        $today = Carbon::today()->toDateString();
        $clockIn = $event->occurredAt ?? now();
        $tenantId = tenant('id') ?? 'default';

        // Check if an open shift already exists for this worker & geofence today
        $existingShift = Timesheet::where('user_id', $event->worker->id)
            ->where('geofence_id', $event->geofence->id)
            ->where('date', $today)
            ->whereNull('clock_out')
            ->first();

        if (! $existingShift) {
            $timesheet = Timesheet::create([
                'user_id'                => $event->worker->id,
                'geofence_id'            => $event->geofence->id,
                'date'                   => $today,
                'clock_in'               => $clockIn,
                'clock_in_time'          => $clockIn,
                'is_automated'           => true,
                'status'                 => 'active',
                'total_minutes'          => 0,
                'shift_duration_minutes' => 0,
            ]);

            try {
                AttendanceUpdated::dispatch($tenantId, $timesheet, 'clock_in');
            } catch (\Exception $e) {
                Log::warning("Broadcasting AttendanceUpdated failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle worker exit geofence event (Clock-Out).
     */
    public function handleWorkerExited(WorkerExitedGeofence $event): void
    {
        $clockOut = $event->occurredAt ?? now();
        $tenantId = tenant('id') ?? 'default';

        // Find the open active timesheet record for this worker and geofence
        $timesheet = Timesheet::where('user_id', $event->worker->id)
            ->where('geofence_id', $event->geofence->id)
            ->where(function ($query) {
                $query->whereNull('clock_out')
                      ->orWhere('status', 'active');
            })
            ->latest('clock_in')
            ->first();

        if (! $timesheet) {
            // Fallback: Check latest active shift for user regardless of geofence ID
            $timesheet = Timesheet::where('user_id', $event->worker->id)
                ->where(function ($query) {
                    $query->whereNull('clock_out')
                          ->orWhere('status', 'active');
                })
                ->latest('clock_in')
                ->first();
        }

        if ($timesheet) {
            $clockIn = $timesheet->clock_in ?? $timesheet->clock_in_time ?? $clockOut;
            $durationMinutes = (int) $clockIn->diffInMinutes($clockOut);

            $timesheet->update([
                'clock_out'              => $clockOut,
                'clock_out_time'         => $clockOut,
                'shift_duration_minutes' => $durationMinutes,
                'total_minutes'          => $durationMinutes,
                'status'                 => 'completed',
            ]);

            try {
                AttendanceUpdated::dispatch($tenantId, $timesheet, 'clock_out');
            } catch (\Exception $e) {
                Log::warning("Broadcasting AttendanceUpdated failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Register the listeners for the subscriber.
     */
    public function subscribe(Dispatcher $events): array
    {
        return [
            WorkerEnteredGeofence::class => 'handleWorkerEntered',
            WorkerExitedGeofence::class  => 'handleWorkerExited',
        ];
    }
}
