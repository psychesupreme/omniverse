<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GeofenceAlertTriggered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The tenant ID.
     *
     * @var string
     */
    public string $tenantId;

    /**
     * The user ID.
     *
     * @var int
     */
    public int $userId;

    /**
     * The geofence name.
     *
     * @var string
     */
    public string $geofenceName;

    /**
     * Create a new event instance.
     */
    public function __construct(string $tenantId, int $userId, string $geofenceName)
    {
        $this->tenantId = $tenantId;
        $this->userId = $userId;
        $this->geofenceName = $geofenceName;
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
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'type' => 'geofence_entry',
            'user_id' => $this->userId,
            'geofence' => $this->geofenceName,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'GeofenceAlertTriggered';
    }
}
