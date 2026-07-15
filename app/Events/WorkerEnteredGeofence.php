<?php

namespace App\Events;

use App\Models\Geofence;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkerEnteredGeofence implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public User $worker;
    public Geofence $geofence;
    public Carbon $occurredAt;

    /**
     * Create a new event instance.
     */
    public function __construct(User $worker, Geofence $geofence, Carbon $occurredAt)
    {
        $this->worker = $worker;
        $this->geofence = $geofence;
        $this->occurredAt = $occurredAt;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel
     */
    public function broadcastOn(): Channel
    {
        $tenantId = tenant('id') ?? 'default';
        return new PrivateChannel('tenant.' . $tenantId . '.dispatch');
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'worker_id'     => $this->worker->id,
            'worker_name'   => $this->worker->name,
            'geofence_id'   => $this->geofence->id,
            'geofence_name' => $this->geofence->name,
            'event_type'    => 'entry',
            'occurred_at'   => $this->occurredAt->toIso8601String(),
        ];
    }
}
