<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $project = $this->getProject();
        
        // Admin can update any project, managers can update their own projects
        return match ($user?->role) {
            'admin' => true,
            'manager' => $project?->manager_id === $user->id,
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
                'regex:/^[\pL\pN\s\-_\.]+$/u',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
            ],
            'status' => [
                'sometimes',
                'string',
                Rule::enum(ProjectStatus::class),
            ],
            'start_date' => [
                'sometimes',
                'date',
            ],
            'end_date' => [
                'sometimes',
                'nullable',
                'date',
                'after:start_date',
            ],
            'manager_id' => [
                'sometimes',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $user = $this->user();
                    $query->where('org_id', $user?->org_id)
                          ->whereIn('role', ['admin', 'manager']);
                }),
            ],
            'budget' => [
                'sometimes',
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Project name may only contain letters, numbers, spaces, hyphens, underscores, and periods.',
            'status.enum' => 'Selected project status is invalid.',
            'start_date.date' => 'Start date must be a valid date.',
            'end_date.after' => 'End date must be after the start date.',
            'manager_id.exists' => 'Selected manager does not exist or cannot manage projects.',
            'budget.numeric' => 'Budget must be a valid number.',
            'budget.min' => 'Budget cannot be negative.',
            'budget.max' => 'Budget is too large.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'project name',
            'description' => 'description',
            'status' => 'status',
            'start_date' => 'start date',
            'end_date' => 'end date',
            'manager_id' => 'project manager',
            'budget' => 'budget',
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

        // Format budget if provided
        if ($this->has('budget') && $this->budget !== null) {
            $data['budget'] = number_format((float) $this->budget, 2, '.', '');
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    protected function passedValidation(): void
    {
        // Additional validation for status transitions
        if ($this->has('status')) {
            $project = $this->getProject();
            $currentStatus = ProjectStatus::from($project?->status ?? 'active');
            $newStatus = ProjectStatus::from($this->validated('status'));

            // Validate status transitions (you can add business rules here)
            if ($currentStatus === ProjectStatus::COMPLETED && $newStatus !== ProjectStatus::ACTIVE) {
                // Only allow reactivating completed projects
                $allowedStatuses = [ProjectStatus::ACTIVE->value];
                $this->failedValidation(
                    validator()->make([], [
                        'status' => Rule::in($allowedStatuses)
                    ])
                );
            }
        }

        // Ensure manager belongs to same organization if changing
        if ($this->has('manager_id')) {
            $user = $this->user();
            $managerId = $this->validated('manager_id');
            
            $managerExists = \App\Models\User::where('id', $managerId)
                ->where('org_id', $user?->org_id)
                ->whereIn('role', ['admin', 'manager'])
                ->exists();

            if (!$managerExists) {
                $this->failedValidation(
                    validator()->make([], [
                        'manager_id' => 'exists:users,id'
                    ])
                );
            }
        }
    }

    private function getProject(): ?\App\Models\Project
    {
        $projectId = $this->route('id');
        return $projectId ? \App\Models\Project::find($projectId) : null;
    }
}
