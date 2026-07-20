<?php

namespace App\Listeners;

use App\Events\WorkerEnteredGeofence;
use App\Events\WorkerExitedGeofence;
use App\Models\Timesheet;
use Carbon\Carbon;
use Illuminate\Events\Dispatcher;

class AutomateWorkerTimesheet
{
    /**
     * Handle worker entry geofence event.
     */
    public function handleWorkerEntered(WorkerEnteredGeofence $event): void
    {
        $today = Carbon::today()->toDateString();

        Timesheet::firstOrCreate(
            [
                'user_id' => $event->worker->id,
                'date'    => $today,
            ],
            [
                'clock_in_time' => $event->occurredAt ?? now(),
                'status'        => 'active',
                'total_minutes' => 0,
            ]
        );
    }

    /**
     * Handle worker exit geofence event.
     */
    public function handleWorkerExited(WorkerExitedGeofence $event): void
    {
        $today = Carbon::today()->toDateString();

        $timesheet = Timesheet::where('user_id', $event->worker->id)
            ->where('date', $today)
            ->first();

        if ($timesheet) {
            $clockOut = $event->occurredAt ?? now();
            $clockIn = $timesheet->clock_in_time ?? $clockOut;
            $totalMinutes = (int) $clockIn->diffInMinutes($clockOut);

            $timesheet->update([
                'clock_out_time' => $clockOut,
                'total_minutes'  => $totalMinutes,
                'status'         => 'completed',
            ]);
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
