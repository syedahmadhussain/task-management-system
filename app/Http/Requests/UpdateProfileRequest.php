<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

final class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

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
            'current_password' => [
                'required_with:password',
                'string',
                'current_password',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Name may only contain letters, spaces, hyphens, apostrophes, and periods.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'current_password.required_with' => 'Current password is required when changing password.',
            'current_password.current_password' => 'The current password is incorrect.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'full name',
            'email' => 'email address',
            'password' => 'new password',
            'current_password' => 'current password',
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
}
