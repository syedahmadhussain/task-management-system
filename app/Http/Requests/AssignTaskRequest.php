<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AssignTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $task = $this->getTask();
        
        // Only admin and managers can assign tasks
        return match ($user?->role) {
            'admin' => true,
            'manager' => $task?->org_id === $user->org_id,
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            'user_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $user = $this->user();
                    $query->where('org_id', $user?->org_id);
                }),
            ],
            'notify_user' => [
                'sometimes',
                'boolean',
            ],
            'assignment_notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Please select a user to assign the task to.',
            'user_id.exists' => 'Selected user does not exist or is not in your organization.',
            'notify_user.boolean' => 'Notify user field must be true or false.',
            'assignment_notes.max' => 'Assignment notes cannot exceed 500 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'user_id' => 'assigned user',
            'notify_user' => 'notify user',
            'assignment_notes' => 'assignment notes',
        ];
    }

    public function prepareForValidation(): void
    {
        $data = [];

        // Set default notification preference
        if (!$this->has('notify_user')) {
            $data['notify_user'] = true;
        }

        // Clean up assignment notes
        if ($this->has('assignment_notes')) {
            $data['assignment_notes'] = trim($this->assignment_notes) ?: null;
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    protected function passedValidation(): void
    {
        $user = $this->user();
        $task = $this->getTask();
        $assignedUserId = $this->validated('user_id');

        // Validate that the assigned user belongs to the same organization
        $assignedUserExists = \App\Models\User::where('id', $assignedUserId)
            ->where('org_id', $user?->org_id)
            ->exists();

        if (!$assignedUserExists) {
            $this->failedValidation(
                validator()->make([], [
                    'user_id' => 'exists:users,id'
                ])
            );
        }

        // Check if task can be assigned (business rules)
        if ($task) {
            $taskStatus = TaskStatus::from($task->status);
            
            // Cannot assign completed or cancelled tasks
            if (in_array($taskStatus, [TaskStatus::COMPLETED, TaskStatus::CANCELLED])) {
                $this->failedValidation(
                    validator()->make([], [
                        'task_status' => 'cannot_assign_completed_task'
                    ])
                );
            }

            // Check if already assigned to the same user
            if ($task->assigned_to === $assignedUserId) {
                $this->failedValidation(
                    validator()->make([], [
                        'user_id' => 'already_assigned'
                    ])
                );
            }
        }

        // Validate user workload (optional business rule)
        $this->validateUserWorkload($assignedUserId);
    }

    private function getTask(): ?\App\Models\Task
    {
        $taskId = $this->route('id');
        return $taskId ? \App\Models\Task::find($taskId) : null;
    }

    private function validateUserWorkload(int $userId): void
    {
        // Business rule: Don't allow more than 20 active tasks per user
        $activeTasksCount = \App\Models\Task::where('assigned_to', $userId)
            ->whereIn('status', [TaskStatus::PENDING->value, TaskStatus::IN_PROGRESS->value])
            ->count();

        if ($activeTasksCount >= 20) {
            $this->failedValidation(
                validator()->make([], [
                    'user_id' => 'user_workload_exceeded'
                ])
            );
        }
    }
}
