<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {}

    public function getAllUsers(): Collection
    {
        return $this->userRepository->all();
    }

    public function getUsersPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->userRepository->paginate($perPage);
    }

    public function findUser(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function findUserWithTasks(int $id): ?User
    {
        return $this->userRepository->findWithTasks($id);
    }

    public function findUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    public function getUsersByOrganization(int $organizationId): Collection
    {
        return $this->userRepository->findByOrganization($organizationId);
    }

    public function getUsersByRole(string $role, int $organizationId = null): Collection
    {
        return $this->userRepository->findByRole($role, $organizationId);
    }

    public function createUser(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        return $this->userRepository->create($data);
    }

    public function updateUser(int $id, array $data): bool
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        
        $user = $this->userRepository->findOrFail($id);
        return $this->userRepository->update($user, $data);
    }

    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->findOrFail($id);
        return $this->userRepository->delete($user);
    }

    public function getTopTaskCompletors(int $organizationId, int $limit = 10): Collection
    {
        return $this->userRepository->getTopTaskCompletors($organizationId, $limit);
    }
}
