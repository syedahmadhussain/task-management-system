<?php

namespace App\Events;

use App\Models\Project;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Project $project,
        public array $changes = []
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('organization.' . $this->project->org_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'project.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'project' => $this->project->load(['manager', 'organization']),
            'changes' => $this->changes,
            'event' => 'project_updated',
            'timestamp' => now()->toISOString(),
        ];
    }
}
