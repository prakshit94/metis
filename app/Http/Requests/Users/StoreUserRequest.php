<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user-create') ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'first_name'    => ['required_without:name', 'string', 'max:100'],
            'middle_name'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'email'         => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password'      => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'is_active'     => ['sometimes', 'boolean'],
            'phone'         => ['sometimes', 'nullable', 'string', 'regex:/^\d{10}$/'],
            'department'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'employee_id'   => ['sometimes', 'nullable', 'string', 'max:100', 'unique:users,employee_id'],
            'photo'         => ['sometimes', 'nullable', 'string'],
            'photo_file'    => ['sometimes', 'nullable', 'image', 'max:2048'],
            'joining_date'  => ['sometimes', 'nullable', 'date'],
            'roles'         => ['sometimes', 'array'],
            'roles.*'       => ['string', 'exists:roles,name'],
            'permissions'   => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $name = trim(implode(' ', array_filter([
            trim((string) $this->input('first_name', '')),
            trim((string) $this->input('middle_name', '')),
            trim((string) $this->input('last_name', '')),
        ])));

        $this->merge([
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'name'  => $name !== '' ? $name : $this->input('name'),
        ]);
    }
}
