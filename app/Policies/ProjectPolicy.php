<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isManager() || $user->isMember();
    }

    public function view(User $user, Project $project): bool
    {
        return $user->org_id === $project->org_id && 
               ($user->isAdmin() || 
                ($user->isManager() && $project->manager_id === $user->id) ||
                ($user->isMember() && $project->tasks()->where('assigned_to', $user->id)->exists()));
    }

    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isManager();
    }

    public function update(User $user, Project $project): bool
    {
        return $user->org_id === $project->org_id && 
               ($user->isAdmin() || 
                ($user->isManager() && $project->manager_id === $user->id));
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->org_id === $project->org_id && 
               ($user->isAdmin() || 
                ($user->isManager() && $project->manager_id === $user->id));
    }
}
