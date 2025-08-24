<?php

namespace App\Contracts\Repositories;

use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationRepositoryInterface extends BaseRepositoryInterface
{
    public function findWithUsers(int $id): ?Organization;
    
    public function findWithProjects(int $id): ?Organization;
    
    public function searchByName(string $name): Collection;
}
