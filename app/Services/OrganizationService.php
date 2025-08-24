<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\Repositories\OrganizationRepositoryInterface;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class OrganizationService
{
    public function __construct(
        private OrganizationRepositoryInterface $organizationRepository
    ) {}

    public function getAllOrganizations(): Collection
    {
        return $this->organizationRepository->all();
    }

    public function getOrganizationsPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->organizationRepository->paginate($perPage);
    }

    public function findOrganization(int $id): ?Organization
    {
        return $this->organizationRepository->find($id);
    }

    public function findOrganizationWithUsers(int $id): ?Organization
    {
        return $this->organizationRepository->findWithUsers($id);
    }

    public function findOrganizationWithProjects(int $id): ?Organization
    {
        return $this->organizationRepository->findWithProjects($id);
    }

    public function createOrganization(array $data): Organization
    {
        return $this->organizationRepository->create($data);
    }

    public function updateOrganization(int $id, array $data): bool
    {
        $organization = $this->organizationRepository->findOrFail($id);
        return $this->organizationRepository->update($organization, $data);
    }

    public function deleteOrganization(int $id): bool
    {
        $organization = $this->organizationRepository->findOrFail($id);
        return $this->organizationRepository->delete($organization);
    }

    public function searchOrganizations(string $name): Collection
    {
        return $this->organizationRepository->searchByName($name);
    }
}
