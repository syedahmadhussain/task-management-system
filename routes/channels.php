<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Organization channel - users can only listen to their organization's updates
Broadcast::channel('organization.{orgId}', function ($user, $orgId) {
    return (int) $user->org_id === (int) $orgId;
});

// Project channel - users can only listen to projects in their organization
Broadcast::channel('project.{projectId}', function ($user, $projectId) {
    $project = \App\Models\Project::find($projectId);
    return $project && (int) $user->org_id === (int) $project->org_id;
});

// Task channel - users can only listen to tasks in their organization
Broadcast::channel('task.{taskId}', function ($user, $taskId) {
    $task = \App\Models\Task::find($taskId);
    return $task && (int) $user->org_id === (int) $task->org_id;
});
