<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\ProjectCreated;
use App\Events\ProjectUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProjectController extends Controller
{
    public function __construct(
        private readonly ProjectService $projectService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $perPage = $request->get('per_page', 50);
        $page = $request->get('page', 1);

        $query = \App\Models\Project::with(['organization', 'tasks', 'manager'])
            ->where('org_id', $user->org_id);

        if ($user->isManager()) {
            $query->where('manager_id', $user->id);
        } elseif ($user->isMember()) {
            $query->whereHas('tasks', function($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        $query->withCount('tasks');

        $projects = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($projects);
    }

    public function show(int $id): JsonResponse
    {
        $project = $this->projectService->findProject($id);

        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $this->authorize('view', $project);

        return response()->json(['data' => $project]);
    }

    public function store(StoreProjectRequest $request): JsonResponse
    {
        $project = $this->projectService->createProject($request->validated());

        ProjectCreated::dispatch($project);

        return response()->json(['data' => $project], 201);
    }

    public function update(UpdateProjectRequest $request, int $id): JsonResponse
    {
        $project = $this->projectService->findProject($id);

        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $this->authorize('update', $project);

        $changes = $request->validated();
        $updated = $this->projectService->updateProject($id, $changes);

        if (!$updated) {
            return response()->json(['error' => 'Failed to update project'], 500);
        }

        $updatedProject = $this->projectService->findProject($id);

        ProjectUpdated::dispatch($updatedProject, $changes);

        return response()->json(['data' => $updatedProject]);
    }

    public function destroy(int $id): JsonResponse
    {
        $project = $this->projectService->findProject($id);

        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $this->authorize('delete', $project);

        $deleted = $this->projectService->deleteProject($id);

        if (!$deleted) {
            return response()->json(['error' => 'Failed to delete project'], 500);
        }

        return response()->json(['message' => 'Project deleted successfully']);
    }

    public function tasks(int $id): JsonResponse
    {
        $project = $this->projectService->findProjectWithTasks($id);

        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $this->authorize('view', $project);

        return response()->json(['data' => $project->tasks]);
    }

    public function statistics(): JsonResponse
    {
        $user = auth()->user();

        $query = \App\Models\Project::where('org_id', $user->org_id);

        if ($user->isManager()) {
            $query->where('manager_id', $user->id);
        } elseif ($user->isMember()) {
            $query->whereHas('tasks', function($q) use ($user) {
                $q->where('assigned_to', $user->id);
            });
        }

        $projects = $query->withCount([
            'tasks',
            'tasks as completed_tasks_count' => function ($query) {
                $query->where('status', 'completed');
            },
            'tasks as pending_tasks_count' => function ($query) {
                $query->where('status', 'pending');
            },
            'tasks as in_progress_tasks_count' => function ($query) {
                $query->where('status', 'in_progress');
            }
        ])->get();

        return response()->json(['data' => $projects]);
    }
}
