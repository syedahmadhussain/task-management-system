<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrganizationRequest;
use App\Http\Requests\UpdateOrganizationRequest;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationService $organizationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);

        if ($request->has('search')) {
            $organizations = $this->organizationService->searchOrganizations(
                (string) $request->get('search')
            );
            return response()->json(['data' => $organizations]);
        }

        $organizations = $this->organizationService->getOrganizationsPaginated($perPage);
        return response()->json($organizations);
    }

    public function show(int $id): JsonResponse
    {
        $organization = $this->organizationService->findOrganization($id);

        if (!$organization) {
            return response()->json(['error' => 'Organization not found'], 404);
        }

        return response()->json(['data' => $organization]);
    }

    public function store(StoreOrganizationRequest $request): JsonResponse
    {
        $organization = $this->organizationService->createOrganization(
            $request->validated()
        );

        return response()->json(['data' => $organization], 201);
    }

    public function update(UpdateOrganizationRequest $request, int $id): JsonResponse
    {
        $updated = $this->organizationService->updateOrganization(
            $id,
            $request->validated()
        );

        if (!$updated) {
            return response()->json(['error' => 'Organization not found'], 404);
        }

        $organization = $this->organizationService->findOrganization($id);
        return response()->json(['data' => $organization]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->organizationService->deleteOrganization($id);

        if (!$deleted) {
            return response()->json(['error' => 'Organization not found'], 404);
        }

        return response()->json(['message' => 'Organization deleted successfully']);
    }

    public function users(int $id): JsonResponse
    {
        $organization = $this->organizationService->findOrganizationWithUsers($id);

        if (!$organization) {
            return response()->json(['error' => 'Organization not found'], 404);
        }

        return response()->json(['data' => $organization->users]);
    }

    public function projects(int $id): JsonResponse
    {
        $organization = $this->organizationService->findOrganizationWithProjects($id);

        if (!$organization) {
            return response()->json(['error' => 'Organization not found'], 404);
        }

        return response()->json(['data' => $organization->projects]);
    }
}
