<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        
        return $user !== null && in_array($user->role, ['admin', 'manager'], true);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'min:3',
            ],
            'description' => [
                'nullable',
                'string',
                'max:2000',
            ],
            'project_id' => [
                'nullable',
                'integer',
                Rule::exists('projects', 'id')->where(function ($query) {
                    $user = $this->user();
                    $query->where('org_id', $user?->org_id);
                }),
            ],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $user = $this->user();
                    $query->where('org_id', $user?->org_id);
                }),
            ],
            'priority' => [
                'required',
                'string',
                Rule::enum(TaskPriority::class),
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::enum(TaskStatus::class),
            ],
            'due_date' => [
                'required',
                'date',
                'after:now',
            ],
            'estimated_time' => [
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
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Task name is required.',
            'name.min' => 'Task name must be at least 3 characters.',
            'project_id.required' => 'Project is required.',
            'project_id.exists' => 'Selected project does not exist or you do not have access to it.',
            'assigned_to.exists' => 'Selected user does not exist or is not in your organization.',
            'priority.required' => 'Task priority is required.',
            'priority.enum' => 'Selected priority is invalid.',
            'status.enum' => 'Selected status is invalid.',
            'due_date.required' => 'Due date is required.',
            'due_date.after' => 'Due date must be in the future.',
            'estimated_time.numeric' => 'Estimated time must be a number.',
            'estimated_time.min' => 'Estimated time must be at least 0.1 hours.',
            'estimated_time.max' => 'Estimated time cannot exceed 999.99 hours.',
            'tags.max' => 'You can add up to 10 tags.',
            'tags.*.regex' => 'Tags may only contain letters, numbers, hyphens, and underscores.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'task name',
            'description' => 'description',
            'project_id' => 'project',
            'assigned_to' => 'assigned user',
            'priority' => 'priority',
            'status' => 'status',
            'due_date' => 'due date',
            'estimated_time' => 'estimated time',
            'tags' => 'tags',
        ];
    }

    public function prepareForValidation(): void
    {
        $data = [
            'name' => trim($this->name ?? ''),
            'description' => trim($this->description ?? '') ?: null,
        ];

        // Set default status if not provided
        if (!$this->has('status')) {
            $data['status'] = TaskStatus::PENDING->value;
        }

        // Set org_id to current user's organization
        $data['org_id'] = $this->user()?->org_id;
        
        // Set user_id to be the same as assigned_to for compatibility
        if ($this->has('assigned_to')) {
            $data['user_id'] = $this->assigned_to;
        }

        // Clean up tags
        if ($this->has('tags') && is_array($this->tags)) {
            $data['tags'] = array_values(array_unique(array_filter(
                array_map('trim', $this->tags),
                fn($tag) => !empty($tag)
            )));
        }

        $this->merge($data);
    }

    protected function passedValidation(): void
    {
        // Validate that the project belongs to the user's organization (if provided)
        $user = $this->user();
        $projectId = $this->validated('project_id');
        
        if ($projectId) {
            $projectExists = \App\Models\Project::where('id', $projectId)
                ->where('org_id', $user?->org_id)
                ->exists();

            if (!$projectExists) {
                $this->failedValidation(
                    validator()->make([], [
                        'project_id' => 'exists:projects,id'
                    ])
                );
            }
        }

        // Validate assigned user if provided
        if ($this->has('assigned_to')) {
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

        // Business rule: Urgent tasks must have estimated time
        $priority = TaskPriority::from($this->validated('priority'));
        if ($priority === TaskPriority::URGENT && !$this->has('estimated_time')) {
            $this->failedValidation(
                validator()->make([], [
                    'estimated_time' => 'required_if:priority,urgent'
                ])
            );
        }
    }
}
