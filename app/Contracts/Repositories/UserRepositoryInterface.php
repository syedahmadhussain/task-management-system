<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;
    
    public function findByOrganization(int $organizationId): Collection;
    
    public function findByRole(string $role, int $organizationId = null): Collection;
    
    public function findWithTasks(int $id): ?User;
    
    public function getTopTaskCompletors(int $organizationId, int $limit = 10): Collection;
}
