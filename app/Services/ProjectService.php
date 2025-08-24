<?php

namespace App\Services;

use App\Contracts\Repositories\ProjectRepositoryInterface;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class ProjectService
{
    public function __construct(
        private ProjectRepositoryInterface $projectRepository
    ) {}

    public function getAllProjects(): Collection
    {
        return $this->projectRepository->all();
    }

    public function getProjectsPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->projectRepository->paginate($perPage);
    }

    public function findProject(int $id): ?Project
    {
        return $this->projectRepository->find($id);
    }

    public function findProjectWithTasks(int $id): ?Project
    {
        return $this->projectRepository->findWithTasks($id);
    }

    public function getProjectsByOrganization(int $organizationId): Collection
    {
        return $this->projectRepository->findByOrganization($organizationId);
    }

    public function getProjectsByStatus(string $status, int $organizationId = null): Collection
    {
        return $this->projectRepository->findByStatus($status, $organizationId);
    }

    public function searchProjects(string $name, int $organizationId = null): Collection
    {
        return $this->projectRepository->searchByName($name, $organizationId);
    }

    public function createProject(array $data): Project
    {
        return $this->projectRepository->create($data);
    }

    public function updateProject(int $id, array $data): bool
    {
        $project = $this->projectRepository->findOrFail($id);
        return $this->projectRepository->update($project, $data);
    }

    public function deleteProject(int $id): bool
    {
        $project = $this->projectRepository->findOrFail($id);
        return $this->projectRepository->delete($project);
    }

    public function getProjectsWithTaskCounts(int $organizationId): Collection
    {
        return $this->projectRepository->getProjectsWithTaskCounts($organizationId);
    }
}
