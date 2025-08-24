<?php

namespace App\Contracts\Repositories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

interface TaskRepositoryInterface extends BaseRepositoryInterface
{
    public function findByProject(int $projectId): Collection;
    
    public function findByUser(int $userId): Collection;
    
    public function findByOrganization(int $organizationId): Collection;
    
    public function findByStatus(string $status, int $organizationId = null): Collection;
    
    public function findByPriority(string $priority, int $organizationId = null): Collection;
    
    public function findDueBefore(Carbon $date, int $organizationId = null): Collection;
    
    public function findSchedulableForUser(int $userId, Carbon $date): Collection;
    
    public function getCompletedTasksByUser(int $userId, int $organizationId = null): int;
    
    public function getTasksWithEstimatedHours(int $organizationId = null): Collection;
    
    public function updateScheduledDate(int $taskId, ?Carbon $scheduledDate): bool;
}
