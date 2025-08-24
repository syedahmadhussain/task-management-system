<?php

namespace App\Repositories;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class ProjectRepository extends BaseRepository implements ProjectRepositoryInterface
{
    public function __construct(Project $project)
    {
        parent::__construct($project);
    }

    public function findByOrganization(int $organizationId): Collection
    {
        return $this->model->where('org_id', $organizationId)->get();
    }

    public function findWithTasks(int $id): ?Project
    {
        return $this->model->with('tasks')->find($id);
    }

    public function findByStatus(string $status, int $organizationId = null): Collection
    {
        $query = $this->model->where('status', $status);
        
        if ($organizationId) {
            $query->where('org_id', $organizationId);
        }
        
        return $query->get();
    }

    public function searchByName(string $name, int $organizationId = null): Collection
    {
        $query = $this->model->where('name', 'LIKE', "%{$name}%");
        
        if ($organizationId) {
            $query->where('org_id', $organizationId);
        }
        
        return $query->get();
    }

    public function getProjectsWithTaskCounts(int $organizationId): Collection
    {
        return $this->model
            ->where('org_id', $organizationId)
            ->withCount([
                'tasks',
                'tasks as completed_tasks_count' => function ($query) {
                    $query->where('status', 'completed');
                },
                'tasks as pending_tasks_count' => function ($query) {
                    $query->where('status', 'pending');
                }
            ])
            ->get();
    }
}
