<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ProjectStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreProjectRequest extends FormRequest
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
                'regex:/^[\pL\pN\s\-_\.]+$/u',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'status' => [
                'required',
                'string',
                Rule::enum(ProjectStatus::class),
            ],
            'start_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after:start_date',
            ],
            'assigned_user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $user = $this->user();
                    $query->where('org_id', $user?->org_id);
                }),
            ],
            'manager_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $user = $this->user();
                    $query->where('org_id', $user?->org_id)
                         ->whereIn('role', ['admin', 'manager']);
                }),
            ],
            'budget' => [
                'nullable',
                'numeric',
                'min:0',
                'max:999999999.99',
            ],
            'priority' => [
                'nullable',
                'integer',
                'min:1',
                'max:5',
            ],
            'org_id' => [
                'required',
                'integer',
                Rule::exists('organizations', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Project name is required.',
            'name.regex' => 'Project name may only contain letters, numbers, spaces, hyphens, underscores, and periods.',
            'status.required' => 'Project status is required.',
            'status.enum' => 'Selected project status is invalid.',
            'start_date.required' => 'Start date is required.',
            'start_date.after_or_equal' => 'Start date must be today or later.',
            'end_date.after' => 'End date must be after the start date.',
            'assigned_user_id.exists' => 'Selected user does not exist in your organization.',
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
            'assigned_user_id' => 'assigned user',
            'budget' => 'budget',
            'priority' => 'priority',
        ];
    }

    public function prepareForValidation(): void
    {
        $data = [
            'name' => trim($this->name ?? ''),
            'description' => trim($this->description ?? '') ?: null,
        ];

        // Set org_id to current user's organization
        $data['org_id'] = $this->user()?->org_id;

        // Format budget if provided
        if ($this->has('budget') && $this->budget !== null) {
            $data['budget'] = number_format((float) $this->budget, 2, '.', '');
        }

        $this->merge($data);
    }

    protected function passedValidation(): void
    {
        // Ensure the assigned user belongs to the same organization (if provided)
        $user = $this->user();
        $assignedUserId = $this->validated('assigned_user_id');
        
        if ($assignedUserId) {
            $userExists = \App\Models\User::where('id', $assignedUserId)
                ->where('org_id', $user?->org_id)
                ->exists();

            if (!$userExists) {
                $this->failedValidation(
                    validator()->make([], [
                        'assigned_user_id' => 'exists:users,id'
                    ])
                );
            }
        }
    }
}
