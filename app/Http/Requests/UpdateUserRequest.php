<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $targetUserId = (int) $this->route('id');
        
        // Users can update themselves, managers can update members, admins can update anyone
        return match ($user?->role) {
            'admin' => true,
            'manager' => $targetUserId === $user->id || $this->isTargetUserMember($targetUserId),
            'member' => $targetUserId === $user->id,
            default => false,
        };
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                'regex:/^[\pL\s\-\'\.]+$/u',
            ],
            'email' => [
                'sometimes',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => [
                'sometimes',
                'string',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
            ],
            'role' => [
                'sometimes',
                'string',
                Rule::enum(UserRole::class),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'role.enum' => 'Selected role is invalid.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'password',
            'role' => 'user role',
        ];
    }

    public function prepareForValidation(): void
    {
        $data = [];

        if ($this->has('name')) {
            $data['name'] = trim($this->name);
        }

        if ($this->has('email')) {
            $data['email'] = strtolower(trim($this->email));
        }

        if (!empty($data)) {
            $this->merge($data);
        }
    }

    protected function passedValidation(): void
    {
        $user = $this->user();
        $targetUserId = (int) $this->route('id');

        // Only allow role changes for appropriate permissions
        if ($this->has('role')) {
            $requestedRole = UserRole::from($this->validated('role'));

            // Members cannot change roles
            if ($user?->role === 'member') {
                $this->failedValidation(
                    validator()->make([], ['role' => 'prohibited'])
                );
            }

            // Managers can only assign member role
            if ($user?->role === 'manager' && $requestedRole !== UserRole::MEMBER) {
                $this->failedValidation(
                    validator()->make([], ['role' => 'in:member'])
                );
            }

            // Prevent users from changing their own role to admin
            if ($targetUserId === $user?->id && $requestedRole === UserRole::ADMIN) {
                $this->failedValidation(
                    validator()->make([], ['role' => 'not_in:admin'])
                );
            }
        }
    }

    private function isTargetUserMember(int $targetUserId): bool
    {
        return \App\Models\User::where('id', $targetUserId)
            ->where('role', UserRole::MEMBER->value)
            ->exists();
    }
}
