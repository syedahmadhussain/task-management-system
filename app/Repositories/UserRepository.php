<?php

namespace App\Repositories;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function findByOrganization(int $organizationId): Collection
    {
        return $this->model->where('org_id', $organizationId)->get();
    }

    public function findByRole(string $role, int $organizationId = null): Collection
    {
        $query = $this->model->where('role', $role);
        
        if ($organizationId) {
            $query->where('org_id', $organizationId);
        }
        
        return $query->get();
    }

    public function findWithTasks(int $id): ?User
    {
        return $this->model->with('tasks')->find($id);
    }

    public function getTopTaskCompletors(int $organizationId, int $limit = 10): Collection
    {
        return $this->model
            ->where('org_id', $organizationId)
            ->withCount(['tasks' => function ($query) {
                $query->where('status', 'completed');
            }])
            ->orderByDesc('tasks_count')
            ->limit($limit)
            ->get();
    }
}
