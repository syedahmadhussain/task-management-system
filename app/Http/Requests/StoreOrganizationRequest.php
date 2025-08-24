<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreOrganizationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('organizations', 'name'),
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                Rule::unique('organizations', 'email'),
            ],
            'phone' => [
                'nullable',
                'string',
                'regex:/^[\+]?[1-9][\d]{0,15}$/',
                'max:20',
            ],
            'address' => [
                'nullable',
                'string',
                'max:500',
            ],
            'website' => [
                'nullable',
                'url:http,https',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Organization name is required.',
            'name.unique' => 'This organization name is already taken.',
            'email.required' => 'Organization email is required.',
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
        $this->merge([
            'name' => trim($this->name ?? ''),
            'email' => strtolower(trim($this->email ?? '')),
            'phone' => preg_replace('/[^\d\+]/', '', $this->phone ?? ''),
            'website' => filter_var($this->website, FILTER_SANITIZE_URL),
        ]);
    }
}
