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

class ProjectCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Project $project
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('organization.' . $this->project->org_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'project.created';
    }

    public function broadcastWith(): array
    {
        return [
            'project' => $this->project->load(['manager', 'organization']),
            'event' => 'project_created',
            'timestamp' => now()->toISOString(),
        ];
    }
}
