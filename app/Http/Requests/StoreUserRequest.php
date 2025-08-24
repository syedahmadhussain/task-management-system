<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class StoreUserRequest extends FormRequest
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
                'regex:/^[\pL\s\-\'\.]+$/u',
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => [
                'required',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'role' => [
                'required',
                'string',
                Rule::enum(UserRole::class),
            ],
            'org_id' => [
                'sometimes',
                'integer',
                Rule::exists('organizations', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'User name is required.',
            'name.regex' => 'Name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.required' => 'Password is required.',
            'role.required' => 'User role is required.',
            'role.enum' => 'Selected role is invalid.',
            'org_id.exists' => 'Selected organization does not exist.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'role' => 'user role',
            'org_id' => 'organization',
        ];
    }

    public function prepareForValidation(): void
    {
        $data = [
            'name' => trim($this->name ?? ''),
            'email' => strtolower(trim($this->email ?? '')),
        ];

        // Set org_id to current user's organization if not provided
        if (!$this->has('org_id')) {
            $data['org_id'] = $this->user()?->org_id;
        }

        $this->merge($data);
    }

    protected function passedValidation(): void
    {
        // Additional validation after basic rules pass
        $requestingUser = $this->user();
        $requestedRole = UserRole::from($this->validated('role'));

        // Managers can only create members
        if ($requestingUser?->role === 'manager' && $requestedRole !== UserRole::MEMBER) {
            $this->failedValidation(
                validator()->make([], [
                    'role' => 'in:member'
                ])
            );
        }

        // Only admin can create users for different organizations
        if ($requestingUser?->role !== 'admin' && $this->validated('org_id') !== $requestingUser?->org_id) {
            $this->failedValidation(
                validator()->make([], [
                    'org_id' => 'same_as_current_user'
                ])
            );
        }
    }
}
