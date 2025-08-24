<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $task = $this->getTask();
        
        // Admin and managers can update any task, members can update their own tasks
        return match ($user?->role) {
            'admin' => true,
            'manager' => $task?->org_id === $user->org_id,
            'member' => $task?->assigned_to === $user->id,
            default => false,
        };
    }

    public function rules(): array
    {
        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                'min:3',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:2000',
            ],
            'assigned_to' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $user = $this->user();
                    $query->where('org_id', $user?->org_id);
                }),
            ],
            'priority' => [
                'sometimes',
                'string',
                Rule::enum(TaskPriority::class),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::enum(TaskStatus::class),
            ],
            'due_date' => [
                'sometimes',
                'date',
            ],
            'estimated_time' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0.1',
                'max:999.99',
            ],
            'tags' => [
                'sometimes',
                'array',
                'max:10',
            ],
            'tags.*' => [
                'string',
                'max:50',
                'regex:/^[a-zA-Z0-9\-_]+$/',
            ],
            'completion_notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.min' => 'Task name must be at least 3 characters.',
            'assigned_to.exists' => 'Selected user does not exist or is not in your organization.',
            'priority.enum' => 'Selected priority is invalid.',
            'status.enum' => 'Selected status is invalid.',
            'due_date.date' => 'Due date must be a valid date.',
            'estimated_time.numeric' => 'Estimated time must be a number.',
            'estimated_time.min' => 'Estimated time must be at least 0.1 hours.',
            'estimated_time.max' => 'Estimated time cannot exceed 999.99 hours.',
            'tags.max' => 'You can add up to 10 tags.',
            'tags.*.regex' => 'Tags may only contain letters, numbers, hyphens, and underscores.',
            'completion_notes.max' => 'Completion notes cannot exceed 1000 characters.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'task name',
            'description' => 'description',
            'assigned_to' => 'assigned user',
            'priority' => 'priority',
            'status' => 'status',
            'due_date' => 'due date',
            'estimated_time' => 'estimated time',
            'tags' => 'tags',
            'completion_notes' => 'completion notes',
        ];
    }

    public function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim($this->name);
        }

        if ($this->has('description')) {
            $data['description'] = $this->description ? trim($this->description) ?: null : null;
        }

        if ($this->has('completion_notes')) {
            $data['completion_notes'] = $this->completion_notes ? trim($this->completion_notes) ?: null : null;
        }

        // Clean up tags
        if ($this->has('tags') && is_array($this->tags)) {
            $data['tags'] = array_values(array_unique(array_filter(
                array_map('trim', $this->tags),
                fn($tag) => !empty($tag)
            )));
        }

        // Set completed_at timestamp when status changes to completed
        if ($this->has('status') && $this->status === TaskStatus::COMPLETED->value) {
            $data['completed_at'] = now();
        } elseif ($this->has('status') && $this->status !== TaskStatus::COMPLETED->value) {
            $data['completed_at'] = null;
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    protected function passedValidation(): void
    {
        $user = $this->user();
        $task = $this->getTask();

        // Validate status transitions
        if ($this->has('status') && $task) {
            $currentStatus = TaskStatus::from($task->status);
            $newStatus = TaskStatus::from($this->validated('status'));

            // Check if the transition is allowed
            if (!$currentStatus->canTransitionTo($newStatus)) {
                $this->failedValidation(
                    validator()->make([], [
                        'status' => 'invalid_transition'
                    ])
                );
            }
        }

        // Validate assigned user if provided
        if ($this->has('assigned_to') && $this->validated('assigned_to')) {
            $assignedUserId = $this->validated('assigned_to');
            
            $userExists = \App\Models\User::where('id', $assignedUserId)
                ->where('org_id', $user?->org_id)
                ->exists();

            if (!$userExists) {
                $this->failedValidation(
                    validator()->make([], [
                        'assigned_to' => 'exists:users,id'
                    ])
                );
            }
        }

        // Members can only update certain fields
        if ($user?->role === 'member') {
            $allowedFields = ['status', 'completion_notes', 'estimated_time'];
            $providedFields = array_keys($this->validated());
            $disallowedFields = array_diff($providedFields, $allowedFields);

            if (!empty($disallowedFields)) {
                $errorMessages = [];
                foreach ($disallowedFields as $field) {
                    $errorMessages[$field] = "Members are not allowed to update the {$field} field.";
                }
                
                $this->failedValidation(
                    validator()->make([], $errorMessages)->setCustomMessages([
                        '*' => 'Members can only update status, completion notes, and estimated time.'
                    ])
                );
            }
        }

        // Optional: Completion notes for completed tasks (removed requirement to unblock status changes)
        // TODO: Consider making completion notes mandatory through UI or add validation later
        // if ($this->has('status') && $this->validated('status') === TaskStatus::COMPLETED->value) {
        //     if (!$this->has('completion_notes') || empty($this->validated('completion_notes'))) {
        //         $this->failedValidation(
        //             validator()->make([], [
        //                 'completion_notes' => 'required_if:status,completed'
        //             ])
        //         );
        //     }
        // }
    }

    private function getTask(): ?\App\Models\Task
    {
        $taskId = $this->route('id');
        return $taskId ? \App\Models\Task::find($taskId) : null;
    }
}
