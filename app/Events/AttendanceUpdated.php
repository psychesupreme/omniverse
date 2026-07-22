<?php

namespace App\Events;

use App\Models\Timesheet;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $tenantId;
    public Timesheet $timesheet;
    public string $eventType; // 'clock_in' or 'clock_out'

    /**
     * Create a new event instance.
     */
    public function __construct(string $tenantId, Timesheet $timesheet, string $eventType)
    {
        $this->tenantId = $tenantId;
        $this->timesheet = $timesheet;
        $this->eventType = $eventType;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->tenantId . '.sync'),
        ];
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'type'                   => 'attendance_updated',
            'event_type'             => $this->eventType,
            'timesheet_id'           => $this->timesheet->id,
            'user_id'                => $this->timesheet->user_id,
            'geofence_id'            => $this->timesheet->geofence_id,
            'clock_in'               => $this->timesheet->clock_in?->toIso8601String() ?? $this->timesheet->clock_in_time?->toIso8601String(),
            'clock_out'              => $this->timesheet->clock_out?->toIso8601String() ?? $this->timesheet->clock_out_time?->toIso8601String(),
            'shift_duration_minutes' => $this->timesheet->shift_duration_minutes ?? $this->timesheet->total_minutes,
            'timestamp'              => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'AttendanceUpdated';
    }
}
