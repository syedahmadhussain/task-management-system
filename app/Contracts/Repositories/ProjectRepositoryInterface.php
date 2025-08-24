<?php

namespace App\Contracts\Repositories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

interface ProjectRepositoryInterface extends BaseRepositoryInterface
{
    public function findByOrganization(int $organizationId): Collection;
    
    public function findWithTasks(int $id): ?Project;
    
    public function findByStatus(string $status, int $organizationId = null): Collection;
    
    public function searchByName(string $name, int $organizationId = null): Collection;
    
    public function getProjectsWithTaskCounts(int $organizationId): Collection;
}
