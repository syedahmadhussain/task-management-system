<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Support\Collection;

final class TaskSchedulingService
{
    private const DEFAULT_HOURS_PER_DAY = 6;

    public function scheduleTasksForUser(int $userId, ?string $startDate = null, float $hoursPerDay = self::DEFAULT_HOURS_PER_DAY): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::today();

        $tasks = $this->getSchedulableTasks($userId);
        $sortedTasks = $this->sortTasksByPriorityAndDueDate($tasks, $startDate);

        return $this->generateSchedule($sortedTasks, $startDate, $hoursPerDay);
    }

    public function scheduleTasksForOrganization(int $orgId, ?string $startDate = null, float $hoursPerDay = self::DEFAULT_HOURS_PER_DAY): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::today();

        $tasks = $this->getSchedulableTasksForOrganization($orgId);
        $sortedTasks = $this->sortTasksByPriorityAndDueDate($tasks, $startDate);

        return $this->generateSchedule($sortedTasks, $startDate, $hoursPerDay);
    }

    public function scheduleTasksForManager(int $managerId, ?string $startDate = null, float $hoursPerDay = self::DEFAULT_HOURS_PER_DAY): array
    {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::today();

        $tasks = $this->getSchedulableTasksForManager($managerId);
        $sortedTasks = $this->sortTasksByPriorityAndDueDate($tasks, $startDate);

        return $this->generateSchedule($sortedTasks, $startDate, $hoursPerDay);
    }

    private function getSchedulableTasks(int $userId): Collection
    {
        return Task::with(['project'])
            ->where('assigned_to', $userId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('estimated_time', '>', 0)
            ->where('estimated_time', '<=', 24)
            ->whereNotNull('due_date')
            ->get();
    }

    private function getSchedulableTasksForOrganization(int $orgId): Collection
    {
        return Task::with(['project', 'user'])
            ->whereHas('project', function($query) use ($orgId) {
                $query->where('org_id', $orgId);
            })
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('estimated_time', '>', 0)
            ->where('estimated_time', '<=', 24)
            ->whereNotNull('due_date')
            ->get();
    }

    private function getSchedulableTasksForManager(int $managerId): Collection
    {
        return Task::with(['project', 'user'])
            ->whereHas('project', function($query) use ($managerId) {
                $query->where('manager_id', $managerId);
            })
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('estimated_time', '>', 0)
            ->where('estimated_time', '<=', 24)
            ->whereNotNull('due_date')
            ->get();
    }

    private function sortTasksByPriorityAndDueDate(Collection $tasks, Carbon $referenceDate): Collection
    {
        return $tasks->sort(function ($a, $b) use ($referenceDate) {
            $scoreA = $this->calculateUrgencyScore($a, $referenceDate);
            $scoreB = $this->calculateUrgencyScore($b, $referenceDate);

            return $scoreB <=> $scoreA;
        })->values();
    }

    private function calculateUrgencyScore(Task $task, Carbon $referenceDate): float
    {
        // Convert string priority to numeric value for calculation
        $priorityNumeric = $this->convertPriorityToNumeric($task->priority);

        // Priority weight (1-4, where 1 is highest priority)
        $priorityScore = (5 - $priorityNumeric) * 2; // 8, 6, 4, 2

        $daysUntilDue = $referenceDate->diffInDays(Carbon::parse($task->due_date), false);
        $dueDateScore = match(true) {
            $daysUntilDue < 0 => 20,    // Overdue
            $daysUntilDue <= 1 => 15,   // Due today/tomorrow
            $daysUntilDue <= 3 => 10,   // Due within 3 days
            $daysUntilDue <= 7 => 5,    // Due within a week
            default => 1                // Due later
        };

        return $priorityScore + $dueDateScore;
    }

    private function convertPriorityToNumeric(string $priority): int
    {
        return match($priority) {
            'urgent' => 1,
            'high' => 2,
            'medium' => 3,
            'low' => 4,
            default => 3 // default to medium if unknown
        };
    }

    private function generateSchedule(Collection $tasks, Carbon $startDate, float $hoursPerDay): array
    {
        $schedule = [];
        $currentDate = $startDate->copy();
        $remainingTasks = $tasks->all();
        $totalTasks = count($remainingTasks);
        $scheduledTasks = 0;
        $totalHours = 0;
        $overdueTasks = 0;

        $maxDays = 365; // Prevent infinite loops
        $dayCount = 0;

        while (!empty($remainingTasks) && $dayCount < $maxDays) {
            $dailyTasks = [];
            $dailyHours = 0;
            $unscheduledTasks = [];
            $tasksScheduledToday = 0;

            foreach ($remainingTasks as $task) {
                $taskHours = (float) $task->estimated_time;

                // Skip tasks that are too big for any single day
                if ($taskHours > $hoursPerDay) {
                    $unscheduledTasks[] = $task;
                    continue;
                }

                if ($dailyHours + $taskHours <= $hoursPerDay) {
                    $tasksScheduledToday++;
                    $isOverdue = Carbon::parse($task->due_date)->lt($currentDate);
                    if ($isOverdue) {
                        $overdueTasks++;
                    }

                    $dailyTasks[] = [
                        'id' => $task->id,
                        'name' => $task->name,
                        'priority' => $task->priority,
                        'estimated_time' => $taskHours,
                        'due_date' => $task->due_date,
                        'project_id' => $task->project_id,
                        'assigned_to' => $task->assigned_to,
                        'is_overdue' => $isOverdue,
                        'days_until_due' => $currentDate->diffInDays(Carbon::parse($task->due_date), false),
                        'urgency_score' => $this->calculateUrgencyScore($task, $currentDate),
                    ];

                    $dailyHours += $taskHours;
                    $scheduledTasks++;
                } else {
                    $unscheduledTasks[] = $task;
                }
            }

            if (!empty($dailyTasks)) {
                $totalHours += $dailyHours;
                $schedule[] = [
                    'date' => $currentDate->toDateString(),
                    'tasks' => $dailyTasks,
                    'total_hours' => $dailyHours,
                    'available_hours' => $hoursPerDay,
                    'is_full_day' => $dailyHours >= $hoursPerDay,
                    'utilization_rate' => round(($dailyHours / $hoursPerDay) * 100, 2),
                ];
            }

            // If no tasks were scheduled today and we still have remaining tasks,
            // it means no more tasks can be fit, so break to avoid infinite loop
            if ($tasksScheduledToday === 0 && !empty($remainingTasks)) {
                break;
            }

            $remainingTasks = $unscheduledTasks;
            $currentDate->addDay();
            $dayCount++;
        }

        return [
            'schedule' => $schedule,
            'statistics' => [
                'total_days' => count($schedule),
                'total_tasks' => $totalTasks,
                'scheduled_tasks' => $scheduledTasks,
                'unscheduled_tasks' => $totalTasks - $scheduledTasks,
                'total_hours' => $totalHours,
                'overdue_tasks' => $overdueTasks,
                'overdue_percentage' => $totalTasks > 0 ? round(($overdueTasks / $totalTasks) * 100, 2) : 0,
                'average_utilization' => count($schedule) > 0 ? round(($totalHours / (count($schedule) * $hoursPerDay)) * 100, 2) : 0,
                'start_date' => $startDate->toDateString(),
                'end_date' => count($schedule) > 0 ? end($schedule)['date'] : $startDate->toDateString(),
            ],
        ];
    }
}
