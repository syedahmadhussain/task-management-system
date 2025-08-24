<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\OrganizationController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:api');
    Route::post('refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
    Route::get('me', [AuthController::class, 'me'])->middleware('auth:api');
    Route::get('profile', [AuthController::class, 'me'])->middleware('auth:api');
});

// Broadcasting authentication route for real-time features
Broadcast::routes(['middleware' => ['auth:api']]);

Route::middleware('auth:api')->group(function () {
    
    Route::prefix('organizations')->middleware(['authorize.resource:Organization'])->group(function () {
        Route::get('/', [OrganizationController::class, 'index'])->middleware('role:admin');
        Route::post('/', [OrganizationController::class, 'store'])->middleware('role:admin');
        Route::get('/{id}', [OrganizationController::class, 'show']);
        Route::put('/{id}', [OrganizationController::class, 'update'])->middleware('role:admin');
        Route::delete('/{id}', [OrganizationController::class, 'destroy'])->middleware('role:admin');
        Route::get('/{id}/users', [OrganizationController::class, 'users']);
        Route::get('/{id}/projects', [OrganizationController::class, 'projects']);
    });
});

Route::middleware(['auth:api', 'ensure.user.belongs.to.organization'])->group(function () {
    
    Route::prefix('users')->middleware(['authorize.resource:User'])->group(function () {
        Route::get('/', [UserController::class, 'index']);
        Route::post('/', [UserController::class, 'store'])->middleware('role:admin');
        Route::get('/{id}', [UserController::class, 'show']);
        Route::put('/{id}', [UserController::class, 'update']);
        Route::delete('/{id}', [UserController::class, 'destroy'])->middleware('role:admin');
        Route::get('/{id}/tasks', [UserController::class, 'tasks']);
    });
    
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    
    Route::prefix('projects')->group(function () {
        Route::get('/', [ProjectController::class, 'index'])->middleware('authorize.resource:Project');
        Route::post('/', [ProjectController::class, 'store'])->middleware(['role:admin,manager', 'authorize.resource:Project']);
        Route::get('/{id}', [ProjectController::class, 'show'])->middleware('authorize.resource:Project');
        Route::put('/{id}', [ProjectController::class, 'update'])->middleware('role:admin,manager');
        Route::delete('/{id}', [ProjectController::class, 'destroy'])->middleware('role:admin,manager');
        Route::get('/{id}/tasks', [ProjectController::class, 'tasks'])->middleware('authorize.resource:Project');
    });
    
    Route::get('/projects/statistics', [ProjectController::class, 'statistics']);
    
    Route::prefix('tasks')->middleware(['authorize.resource:Task'])->group(function () {
        Route::get('/', [TaskController::class, 'index']);
        Route::post('/', [TaskController::class, 'store'])->middleware('role:admin,manager');
        Route::get('/schedule', [TaskController::class, 'schedule'])->middleware('role:admin,manager'); // Team schedule
        Route::get('/{id}', [TaskController::class, 'show']);
        Route::put('/{id}', [TaskController::class, 'update']);
        Route::delete('/{id}', [TaskController::class, 'destroy'])->middleware('role:admin,manager');
        Route::post('/{id}/assign', [TaskController::class, 'assign'])->middleware('role:admin,manager');
        Route::patch('/{id}/complete', [TaskController::class, 'complete']);
    });
    
    Route::get('/my-tasks', [TaskController::class, 'myTasks']);
    Route::get('/my-schedule', [TaskController::class, 'mySchedule']);
    
    Route::prefix('reports')->group(function () {
        Route::get('/task-completions', [ReportController::class, 'taskCompletions'])->middleware('role:admin');
        Route::get('/task-stats', [ReportController::class, 'taskStats']);
        Route::get('/user-performance', [ReportController::class, 'userPerformance'])->middleware('role:admin');
        Route::get('/project-performance', [ReportController::class, 'projectPerformance'])->middleware('role:admin');
        Route::get('/top-performers', [ReportController::class, 'topPerformers'])->middleware('role:admin');
        Route::get('/overview', [ReportController::class, 'organizationOverview'])->middleware('role:admin');
        Route::get('/admin-dashboard', [ReportController::class, 'adminDashboard'])->middleware('role:admin');
        Route::get('/dashboard', [ReportController::class, 'dashboard']);
    });
});
