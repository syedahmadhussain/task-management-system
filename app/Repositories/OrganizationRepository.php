<?php

namespace App\Repositories;

use App\Contracts\Repositories\OrganizationRepositoryInterface;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;

class OrganizationRepository extends BaseRepository implements OrganizationRepositoryInterface
{
    public function __construct(Organization $organization)
    {
        parent::__construct($organization);
    }

    public function findWithUsers(int $id): ?Organization
    {
        return $this->model->with('users')->find($id);
    }

    public function findWithProjects(int $id): ?Organization
    {
        return $this->model->with('projects')->find($id);
    }

    public function searchByName(string $name): Collection
    {
        return $this->model
            ->where('name', 'LIKE', "%{$name}%")
            ->get();
    }
}
