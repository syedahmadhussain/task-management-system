<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly UserService $userService
    ) {}

    public function taskCompletions(Request $request): JsonResponse
    {
        $user = auth()->user();
        $orgId = $request->get('org_id', $user->org_id);

        $topCompletions = $this->reportService->getTopTaskCompletionsByOrganization($orgId);

        return response()->json(['data' => $topCompletions]);
    }

    public function taskStats(): JsonResponse
    {
        $user = auth()->user();
        $stats = $this->reportService->getTaskCompletionStats($user->org_id);

        return response()->json(['data' => $stats]);
    }

    public function userPerformance(): JsonResponse
    {
        $user = auth()->user();
        $performance = $this->reportService->getUserPerformanceMetrics($user->org_id);

        return response()->json(['data' => $performance]);
    }

    public function projectPerformance(): JsonResponse
    {
        $user = auth()->user();
        $performance = $this->reportService->getProjectPerformanceMetrics($user->org_id);

        return response()->json(['data' => $performance]);
    }

    public function topPerformers(Request $request): JsonResponse
    {
        $user = auth()->user();
        $limit = $request->get('limit', 10);

        $topPerformers = $this->userService->getTopTaskCompletors($user->org_id, $limit);

        return response()->json(['data' => $topPerformers]);
    }

    public function organizationOverview(): JsonResponse
    {
        $user = auth()->user();

        $overview = [
            'task_stats' => $this->reportService->getTaskCompletionStats($user->org_id),
            'top_performers' => $this->userService->getTopTaskCompletors($user->org_id, 5)->toArray(),
            'user_performance' => $this->reportService->getUserPerformanceMetrics($user->org_id),
            'project_performance' => $this->reportService->getProjectPerformanceMetrics($user->org_id)
        ];

        return response()->json(['data' => $overview]);
    }

    public function dashboard(): JsonResponse
    {
        $user = auth()->user();

        $dashboard = [
            'organization_id' => $user->org_id,
            'summary' => [
                'task_completion_stats' => $this->reportService->getTaskCompletionStats($user->org_id)['overall_stats'],
                'top_3_performers' => $this->userService->getTopTaskCompletors($user->org_id, 3)->toArray(),
                'project_count' => count($this->reportService->getProjectPerformanceMetrics($user->org_id)),
                'user_count' => $this->userService->getUsersByOrganization($user->org_id)->count()
            ],
            'charts' => [
                'priority_breakdown' => $this->reportService->getTaskCompletionStats($user->org_id)['priority_breakdown'],
                'user_performance_overview' => array_slice($this->reportService->getUserPerformanceMetrics($user->org_id), 0, 10),
                'project_completion_rates' => array_map(function($project) {
                    return [
                        'name' => $project['name'],
                        'completion_rate' => $project['completion_rate']
                    ];
                }, $this->reportService->getProjectPerformanceMetrics($user->org_id))
            ]
        ];

        return response()->json(['data' => $dashboard]);
    }

    public function adminDashboard(): JsonResponse
    {
        $user = auth()->user();

        $adminDashboard = [
            'admin_organization_id' => $user->org_id,
            'cross_organization_analytics' => [
                'total_organizations' => \App\Models\Organization::count(),
                'total_users' => \App\Models\User::count(),
                'total_projects' => \App\Models\Project::count(),
                'total_tasks' => \App\Models\Task::count(),
                'completed_tasks' => \App\Models\Task::where('status', 'completed')->count(),
            ],
            'organization_performance' => $this->getOrganizationPerformanceMetrics(),
            'top_performers_per_organization' => $this->getTopPerformersPerOrganization(),
        ];

        return response()->json(['data' => $adminDashboard]);
    }

    private function getOrganizationPerformanceMetrics(): array
    {
        $organizations = \App\Models\Organization::with(['users', 'projects', 'projects.tasks'])->get();

        return $organizations->map(function($org) {
            $totalTasks = $org->projects->sum(function($project) {
                return $project->tasks->count();
            });

            $completedTasks = $org->projects->sum(function($project) {
                return $project->tasks->where('status', 'completed')->count();
            });

            return [
                'organization_id' => $org->id,
                'organization_name' => $org->name,
                'total_users' => $org->users->count(),
                'total_projects' => $org->projects->count(),
                'total_tasks' => $totalTasks,
                'completed_tasks' => $completedTasks,
                'completion_rate' => $totalTasks > 0 ? round(($completedTasks / $totalTasks) * 100, 2) : 0,
            ];
        })->toArray();
    }

    private function getTopPerformersPerOrganization(): array
    {
        $organizations = \App\Models\Organization::all();
        $thirtyDaysAgo = now()->subDays(30);

        return $organizations->map(function($org) use ($thirtyDaysAgo) {
            $topPerformers = \App\Models\User::where('org_id', $org->id)
                ->withCount(['tasks as completed_tasks_count' => function($query) use ($thirtyDaysAgo) {
                    $query->where('status', 'completed')
                          ->where('updated_at', '>=', $thirtyDaysAgo);
                }])
                ->orderByDesc('completed_tasks_count')
                ->limit(3)
                ->get(['id', 'name', 'email', 'role']);

            return [
                'organization_id' => $org->id,
                'organization_name' => $org->name,
                'top_3_performers' => $topPerformers->map(function($user) {
                    return [
                        'user_id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'completed_tasks_last_30_days' => $user->completed_tasks_count,
                    ];
                })->toArray(),
            ];
        })->toArray();
    }
}
