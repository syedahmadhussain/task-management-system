<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        if ($request->has('role')) {
            $users = $this->userService->getUsersByRole($request->get('role'), $user->org_id);
            return response()->json(['data' => $users->values()]);
        }

        if ($request->has('organization_id')) {
            $users = $this->userService->getUsersByOrganization($request->get('organization_id'));
            return response()->json(['data' => $users->values()]);
        }

        $users = $this->userService->getUsersByOrganization($user->org_id);

        $users = $users->where('role', 'member');

        return response()->json(['data' => $users->values()]);
    }

    public function show(int $id): JsonResponse
    {
        $user = $this->userService->findUser($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json(['data' => $user]);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->createUser($request->validated());

        return response()->json(['data' => $user], 201);
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $updated = $this->userService->updateUser($id, $request->validated());

        if (!$updated) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user = $this->userService->findUser($id);
        return response()->json(['data' => $user]);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->userService->deleteUser($id);

        if (!$deleted) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function tasks(int $id): JsonResponse
    {
        $user = $this->userService->findUserWithTasks($id);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json(['data' => $user->tasks]);
    }

    public function profile(): JsonResponse
    {
        $user = auth()->user();
        return response()->json(['data' => $user]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth()->user();

        $updated = $this->userService->updateUser($user->id, $request->validated());

        if (!$updated) {
            return response()->json(['error' => 'Failed to update profile'], 500);
        }

        $updatedUser = $this->userService->findUser($user->id);
        return response()->json(['data' => $updatedUser]);
    }
}
