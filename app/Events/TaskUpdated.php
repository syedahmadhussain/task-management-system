<?php

namespace App\Events;

use App\Models\Task;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TaskUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Task $task,
        public array $changes = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('organization.' . $this->task->org_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'task.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'task' => $this->task->load(['project', 'assignedUser', 'creator']),
            'changes' => $this->changes,
            'event' => 'task_updated',
            'timestamp' => now()->toISOString(),
        ];
    }
}
