<?php

namespace App\Services;

use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class TaskService
{
    public function __construct(
        private TaskRepositoryInterface $taskRepository
    ) {}

    public function getAllTasks(): Collection
    {
        return $this->taskRepository->all();
    }

    public function getTasksPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->taskRepository->paginate($perPage);
    }

    public function findTask(int $id): ?Task
    {
        return $this->taskRepository->find($id);
    }

    public function getTasksByProject(int $projectId): Collection
    {
        return $this->taskRepository->findByProject($projectId);
    }

    public function getTasksByUser(int $userId): Collection
    {
        return $this->taskRepository->findByUser($userId);
    }

    public function getTasksByOrganization(int $organizationId): Collection
    {
        return $this->taskRepository->findByOrganization($organizationId);
    }

    public function getTasksByStatus(string $status, int $organizationId = null): Collection
    {
        return $this->taskRepository->findByStatus($status, $organizationId);
    }

    public function getTasksByPriority(string $priority, int $organizationId = null): Collection
    {
        return $this->taskRepository->findByPriority($priority, $organizationId);
    }

    public function getOverdueTasks(int $organizationId = null): Collection
    {
        return $this->taskRepository->findDueBefore(Carbon::now(), $organizationId);
    }

    public function createTask(array $data): Task
    {
        return $this->taskRepository->create($data);
    }

    public function updateTask(int $id, array $data): bool
    {
        $task = $this->taskRepository->findOrFail($id);
        return $this->taskRepository->update($task, $data);
    }

    public function deleteTask(int $id): bool
    {
        $task = $this->taskRepository->findOrFail($id);
        return $this->taskRepository->delete($task);
    }

    public function assignTask(int $taskId, int $userId): bool
    {
        return $this->updateTask($taskId, ['assigned_to' => $userId]);
    }

    public function markTaskComplete(int $taskId): bool
    {
        return $this->updateTask($taskId, [
            'status' => 'completed',
            'completed_at' => Carbon::now()
        ]);
    }

    public function getTasksDueBefore(Carbon $date, int $organizationId = null): Collection
    {
        return $this->taskRepository->findDueBefore($date, $organizationId);
    }
}
