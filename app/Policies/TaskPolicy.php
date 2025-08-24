<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        // Check organization membership first
        if ($user->org_id !== $task->org_id) {
            return false;
        }
        
        // Admins can view all tasks
        if ($user->isAdmin()) {
            return true;
        }
        
        // Load project if not already loaded
        if (!$task->relationLoaded('project')) {
            $task->load('project');
        }
        
        return ($user->isManager() && $task->project->manager_id === $user->id) ||
               $task->assigned_to === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Task $task): bool
    {
        // Check organization membership first
        if ($user->org_id !== $task->org_id) {
            return false;
        }
        
        // Admins can update all tasks
        if ($user->isAdmin()) {
            return true;
        }
        
        // Load project if not already loaded
        if (!$task->relationLoaded('project')) {
            $task->load('project');
        }
        
        return ($user->isManager() && $task->project->manager_id === $user->id) ||
               ($user->isMember() && $task->assigned_to === $user->id);
    }

    public function delete(User $user, Task $task): bool
    {
        // Check organization membership first
        if ($user->org_id !== $task->org_id) {
            return false;
        }
        
        // Admins can delete all tasks
        if ($user->isAdmin()) {
            return true;
        }
        
        // Load project if not already loaded
        if (!$task->relationLoaded('project')) {
            $task->load('project');
        }
        
        return $user->isManager() && $task->project->manager_id === $user->id;
    }
}
