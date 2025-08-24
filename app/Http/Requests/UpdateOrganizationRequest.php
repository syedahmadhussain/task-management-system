<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        $organizationId = $this->route('id');

        return [
            'name' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('organizations', 'name')->ignore($organizationId),
            ],
            'email' => [
                'sometimes',
                'email:rfc,dns',
                'max:255',
                Rule::unique('organizations', 'email')->ignore($organizationId),
            ],
            'phone' => [
                'sometimes',
                'nullable',
                'string',
                'regex:/^[\+]?[1-9][\d]{0,15}$/',
                'max:20',
            ],
            'address' => [
                'sometimes',
                'nullable',
                'string',
                'max:500',
            ],
            'website' => [
                'sometimes',
                'nullable',
                'url:http,https',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'This organization name is already taken.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email is already registered.',
            'phone.regex' => 'Please provide a valid phone number.',
            'website.url' => 'Please provide a valid URL starting with http:// or https://',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'organization name',
            'email' => 'email address',
            'phone' => 'phone number',
            'address' => 'address',
            'website' => 'website URL',
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

        if ($this->has('phone')) {
            $data['phone'] = preg_replace('/[^\d\+]/', '', $this->phone ?? '');
        }

        if ($this->has('website')) {
            $data['website'] = filter_var($this->website, FILTER_SANITIZE_URL);
        }

        $this->merge($data);
    }
}
