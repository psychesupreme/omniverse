<?php

namespace App\Events;

use App\Models\Task;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskAssigned implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Task $task;

    /**
     * Create a new event instance.
     */
    public function __construct(Task $task)
    {
        $this->task = $task;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): Channel
    {
        $tenantId = tenant('id') ?? 'default';
        return new PrivateChannel('tenant.' . $tenantId . '.worker.' . $this->task->assigned_user_id);
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'task_id'       => $this->task->id,
            'title'         => $this->task->title,
            'scheduled_for' => $this->task->scheduled_for instanceof \Carbon\Carbon
                ? $this->task->scheduled_for->toIso8601String()
                : $this->task->scheduled_for,
            'status'        => $this->task->status,
        ];
    }
}
