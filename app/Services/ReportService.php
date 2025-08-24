<?php

namespace App\Services;

use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\TaskRepositoryInterface;
use App\Contracts\Repositories\ProjectRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private TaskRepositoryInterface $taskRepository,
        private ProjectRepositoryInterface $projectRepository
    ) {}

    public function getTopTaskCompletionsByOrganization(int $orgId = null): array
    {
        if ($orgId) {
            return [$orgId => $this->userRepository->getTopTaskCompletors($orgId, 3)->toArray()];
        }
        
        $query = DB::table('tasks')
            ->select([
                'u.org_id',
                'u.id as user_id', 
                'u.name as user_name',
                'u.email as user_email',
                DB::raw('COUNT(tasks.id) as completed_count')
            ])
            ->join('users as u', 'tasks.assigned_to', '=', 'u.id')
            ->join('projects as p', 'tasks.project_id', '=', 'p.id')
            ->where('tasks.status', 'completed')
            ->where('tasks.completed_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('u.org_id', 'u.id', 'u.name', 'u.email')
            ->orderBy('u.org_id')
            ->orderBy('completed_count', 'desc')
            ->get();

        $groupedResults = [];
        foreach ($query as $result) {
            $orgId = $result->org_id;
            if (!isset($groupedResults[$orgId])) {
                $groupedResults[$orgId] = [];
            }
            
            if (count($groupedResults[$orgId]) < 3) {
                $groupedResults[$orgId][] = [
                    'user_id' => $result->user_id,
                    'user_name' => $result->user_name,
                    'user_email' => $result->user_email,
                    'completed_count' => $result->completed_count
                ];
            }
        }

        return $groupedResults;
    }

    public function getTaskCompletionStats(int $orgId): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        $stats = DB::table('tasks')
            ->select([
                DB::raw('COUNT(*) as total_tasks'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed_tasks'),
                DB::raw('SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_tasks'),
                DB::raw('SUM(CASE WHEN status = "in_progress" THEN 1 ELSE 0 END) as in_progress_tasks'),
                DB::raw('SUM(CASE WHEN due_date < CURDATE() AND status != "completed" THEN 1 ELSE 0 END) as overdue_tasks'),
                DB::raw('AVG(CASE WHEN status = "completed" THEN estimated_time ELSE NULL END) as avg_completion_time'),
                DB::raw('SUM(CASE WHEN completed_at >= ? AND status = "completed" THEN 1 ELSE 0 END) as completed_last_30_days')
            ])
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('projects.org_id', $orgId)
            ->setBindings([$thirtyDaysAgo])
            ->first();

        $priorityStats = DB::table('tasks')
            ->select([
                'priority',
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed')
            ])
            ->join('projects', 'tasks.project_id', '=', 'projects.id')
            ->where('projects.org_id', $orgId)
            ->groupBy('priority')
            ->orderBy('priority')
            ->get();

        return [
            'overall_stats' => [
                'total_tasks' => (int) $stats->total_tasks,
                'completed_tasks' => (int) $stats->completed_tasks,
                'pending_tasks' => (int) $stats->pending_tasks,
                'in_progress_tasks' => (int) $stats->in_progress_tasks,
                'overdue_tasks' => (int) $stats->overdue_tasks,
                'completion_rate' => $stats->total_tasks > 0 ? round(($stats->completed_tasks / $stats->total_tasks) * 100, 2) : 0,
                'avg_completion_time' => round((float) $stats->avg_completion_time, 2),
                'completed_last_30_days' => (int) $stats->completed_last_30_days
            ],
            'priority_breakdown' => $priorityStats->map(function ($stat) {
                return [
                    'priority' => $stat->priority,
                    'total' => $stat->total,
                    'completed' => $stat->completed,
                    'completion_rate' => $stat->total > 0 ? round(($stat->completed / $stat->total) * 100, 2) : 0
                ];
            })
        ];
    }

    public function getUserPerformanceMetrics(int $orgId): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        $userStats = DB::table('users')
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.role',
                DB::raw('COUNT(tasks.id) as total_assigned_tasks'),
                DB::raw('SUM(CASE WHEN tasks.status = "completed" THEN 1 ELSE 0 END) as completed_tasks'),
                DB::raw('SUM(CASE WHEN tasks.status = "completed" AND tasks.completed_at >= ? THEN 1 ELSE 0 END) as completed_last_30_days'),
                DB::raw('SUM(CASE WHEN tasks.due_date < CURDATE() AND tasks.status != "completed" THEN 1 ELSE 0 END) as overdue_tasks'),
                DB::raw('AVG(CASE WHEN tasks.status = "completed" THEN tasks.estimated_time ELSE NULL END) as avg_task_duration')
            ])
            ->leftJoin('tasks', 'users.id', '=', 'tasks.user_id')
            ->where('users.org_id', $orgId)
            ->groupBy('users.id', 'users.name', 'users.email', 'users.role')
            ->setBindings([$thirtyDaysAgo])
            ->get();

        return $userStats->map(function ($user) {
            return [
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'total_assigned_tasks' => (int) $user->total_assigned_tasks,
                'completed_tasks' => (int) $user->completed_tasks,
                'completed_last_30_days' => (int) $user->completed_last_30_days,
                'overdue_tasks' => (int) $user->overdue_tasks,
                'completion_rate' => $user->total_assigned_tasks > 0 ? round(($user->completed_tasks / $user->total_assigned_tasks) * 100, 2) : 0,
                'avg_task_duration' => round((float) $user->avg_task_duration, 2)
            ];
        })->toArray();
    }

    public function getProjectPerformanceMetrics(int $orgId): array
    {
        $projectStats = DB::table('projects')
            ->select([
                'projects.id',
                'projects.name',
                'projects.description',
                'manager.name as manager_name',
                DB::raw('COUNT(tasks.id) as total_tasks'),
                DB::raw('SUM(CASE WHEN tasks.status = "completed" THEN 1 ELSE 0 END) as completed_tasks'),
                DB::raw('SUM(CASE WHEN tasks.status = "pending" THEN 1 ELSE 0 END) as pending_tasks'),
                DB::raw('SUM(CASE WHEN tasks.due_date < CURDATE() AND tasks.status != "completed" THEN 1 ELSE 0 END) as overdue_tasks'),
                DB::raw('AVG(tasks.estimated_time) as avg_task_time')
            ])
            ->leftJoin('tasks', 'projects.id', '=', 'tasks.project_id')
            ->leftJoin('users as manager', 'projects.manager_id', '=', 'manager.id')
            ->where('projects.org_id', $orgId)
            ->groupBy('projects.id', 'projects.name', 'projects.description', 'manager.name')
            ->get();

        return $projectStats->map(function ($project) {
            return [
                'project_id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'manager_name' => $project->manager_name,
                'total_tasks' => (int) $project->total_tasks,
                'completed_tasks' => (int) $project->completed_tasks,
                'pending_tasks' => (int) $project->pending_tasks,
                'overdue_tasks' => (int) $project->overdue_tasks,
                'completion_rate' => $project->total_tasks > 0 ? round(($project->completed_tasks / $project->total_tasks) * 100, 2) : 0,
                'avg_task_time' => round((float) $project->avg_task_time, 2)
            ];
        })->toArray();
    }
}
