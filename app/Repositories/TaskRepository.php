<?php

namespace App\Repositories;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class TaskRepository extends BaseRepository implements TaskRepositoryInterface
{
    public function __construct(Task $task)
    {
        parent::__construct($task);
    }

    public function findByProject(int $projectId): Collection
    {
        return $this->model->where('project_id', $projectId)->get();
    }

    public function findByUser(int $userId): Collection
    {
        return $this->model->where('user_id', $userId)->get();
    }

    public function findByOrganization(int $organizationId): Collection
    {
        return $this->model->where('org_id', $organizationId)->get();
    }

    public function findByStatus(string $status, int $organizationId = null): Collection
    {
        $query = $this->model->where('status', $status);
        
        if ($organizationId) {
            $query->where('org_id', $organizationId);
        }
        
        return $query->get();
    }

    public function findByPriority(string $priority, int $organizationId = null): Collection
    {
        $query = $this->model->where('priority', $priority);
        
        if ($organizationId) {
            $query->where('org_id', $organizationId);
        }
        
        return $query->get();
    }

    public function findDueBefore(Carbon $date, int $organizationId = null): Collection
    {
        $query = $this->model
            ->where('due_date', '<=', $date)
            ->where('status', '!=', 'completed');
        
        if ($organizationId) {
            $query->where('org_id', $organizationId);
        }
        
        return $query->get();
    }

    public function findSchedulableForUser(int $userId, Carbon $date): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->whereNull('scheduled_date')
            ->orWhere('scheduled_date', '<=', $date)
            ->orderBy('priority', 'desc')
            ->orderBy('due_date', 'asc')
            ->get();
    }

    public function getCompletedTasksByUser(int $userId, int $organizationId = null): int
    {
        $query = $this->model
            ->where('user_id', $userId)
            ->where('status', 'completed');
        
        if ($organizationId) {
            $query->where('org_id', $organizationId);
        }
        
        return $query->count();
    }

    public function getTasksWithEstimatedHours(int $organizationId = null): Collection
    {
        $query = $this->model->whereNotNull('estimated_hours');
        
        if ($organizationId) {
            $query->where('org_id', $organizationId);
        }
        
        return $query->get();
    }

    public function updateScheduledDate(int $taskId, ?Carbon $scheduledDate): bool
    {
        return $this->model
            ->where('id', $taskId)
            ->update(['scheduled_date' => $scheduledDate]);
    }
}
