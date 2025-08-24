<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\TaskCreated;
use App\Events\TaskUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTaskRequest;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Services\TaskService;
use App\Services\TaskSchedulingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly TaskSchedulingService $schedulingService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        $perPage = $request->get('per_page', 20);
        $page = $request->get('page', 1);

        $query = \App\Models\Task::with(['project', 'user'])
            ->whereHas('project', function ($q) use ($user) {
                $q->where('org_id', $user->org_id);
            });

        if ($user->isManager()) {
            $query->whereHas('project', function($q) use ($user) {
                $q->where('manager_id', $user->id);
            });
        } elseif ($user->isMember()) {
            $query->where('assigned_to', $user->id);
        }

        if ($request->has('status') && is_array($request->get('status'))) {
            $query->whereIn('status', $request->get('status'));
        } elseif ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('priority') && is_array($request->get('priority'))) {
            $query->whereIn('priority', $request->get('priority'));
        } elseif ($request->has('priority')) {
            $query->where('priority', $request->get('priority'));
        }

        if ($request->has('project_id') && is_array($request->get('project_id'))) {
            $query->whereIn('project_id', $request->get('project_id'));
        } elseif ($request->has('project_id')) {
            $query->where('project_id', $request->get('project_id'));
        }

        if ($request->has('assignee_id') && is_array($request->get('assignee_id'))) {
            $query->whereIn('assigned_to', $request->get('assignee_id'));
        } elseif ($request->has('assignee_id')) {
            $query->where('assigned_to', $request->get('assignee_id'));
        }

        if ($request->has('due_date_from')) {
            $query->where('due_date', '>=', $request->get('due_date_from'));
        }

        if ($request->has('due_date_to')) {
            $query->where('due_date', '<=', $request->get('due_date_to'));
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        if ($sortBy === 'priority') {
            $query->orderByRaw("CASE priority WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'medium' THEN 3 WHEN 'low' THEN 4 END " . $sortDirection);
        } else {
            $query->orderBy($sortBy, $sortDirection);
        }

        $tasks = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json($tasks);
    }

    public function show(int $id): JsonResponse
    {
        $task = $this->taskService->findTask($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        // Authorize the view action
        $this->authorize('view', $task);

        return response()->json(['data' => $task]);
    }

    public function store(StoreTaskRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $request->validated();
        $data['user_id'] = $user->id;
        $data['org_id'] = $user->org_id;

        $task = $this->taskService->createTask($data);

        TaskCreated::dispatch($task);

        return response()->json(['data' => $task], 201);
    }

    public function update(UpdateTaskRequest $request, int $id): JsonResponse
    {
        $originalTask = $this->taskService->findTask($id);
        if (!$originalTask) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $this->authorize('update', $originalTask);

        $changes = $request->validated();
        $updated = $this->taskService->updateTask($id, $changes);

        if (!$updated) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $task = $this->taskService->findTask($id);

        TaskUpdated::dispatch($task, $changes);

        return response()->json(['data' => $task]);
    }

    public function destroy(int $id): JsonResponse
    {
        $task = $this->taskService->findTask($id);

        if (!$task) {
            return response()->json(['error' => 'Task not found'], 404);
        }

        $this->authorize('delete', $task);

        $deleted = $this->taskService->deleteTask($id);

        if (!$deleted) {
            return response()->json(['error' => 'Failed to delete task'], 500);
        }

        return response()->json(['message' => 'Task deleted successfully']);
    }

    public function assign(AssignTaskRequest $request, int $id): JsonResponse
    {
        $assigned = $this->taskService->assignTask($id, $request->validated('user_id'));

        if (!$assigned) {
            return response()->json(['error' => 'Task not found or assignment failed'], 404);
        }

        $task = $this->taskService->findTask($id);

        TaskUpdated::dispatch($task, ['assigned_to' => $request->validated('user_id')]);

        return response()->json(['data' => $task]);
    }

    public function complete(int $id): JsonResponse
    {
        $completed = $this->taskService->markTaskComplete($id);

        if (!$completed) {
            return response()->json(['error' => 'Task not found or completion failed'], 404);
        }

        $task = $this->taskService->findTask($id);

        TaskUpdated::dispatch($task, ['status' => 'completed']);

        return response()->json(['data' => $task]);
    }

    public function myTasks(Request $request): JsonResponse
    {
        $user = auth()->user();
        $tasks = $this->taskService->getTasksByUser($user->id);

        if ($request->has('status')) {
            $tasks = $tasks->where('status', $request->get('status'));
        }

        return response()->json(['data' => $tasks->values()]);
    }

    public function schedule(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'hours_per_day' => 'nullable|numeric|min:1|max:24',
        ]);

        $startDate = $validated['start_date'] ?? null;
        $hoursPerDay = (float) ($validated['hours_per_day'] ?? 6);

        if ($user->isAdmin()) {
            // Admins see organization-wide schedule
            $schedule = $this->schedulingService->scheduleTasksForOrganization(
                $user->org_id,
                $startDate,
                $hoursPerDay
            );
        } elseif ($user->isManager()) {
            // Managers see their managed projects' tasks
            $schedule = $this->schedulingService->scheduleTasksForManager(
                $user->id,
                $startDate,
                $hoursPerDay
            );
        } else {
            // This shouldn't happen as route is restricted to admin,manager
            $schedule = $this->schedulingService->scheduleTasksForUser(
                $user->id,
                $startDate,
                $hoursPerDay
            );
        }

        return response()->json(['data' => $schedule]);
    }

    public function mySchedule(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'start_date' => 'nullable|date',
            'hours_per_day' => 'nullable|numeric|min:1|max:24',
        ]);

        $startDate = $validated['start_date'] ?? null;
        $hoursPerDay = (float) ($validated['hours_per_day'] ?? 6);

        $schedule = $this->schedulingService->scheduleTasksForUser(
            $user->id,
            $startDate,
            $hoursPerDay
        );

        return response()->json(['data' => $schedule]);
    }

}
