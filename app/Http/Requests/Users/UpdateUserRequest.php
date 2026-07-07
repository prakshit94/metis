<?php

declare(strict_types=1);

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('user-edit') ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'name'          => ['sometimes', 'string', 'max:255'],
            'first_name'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'middle_name'   => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name'     => ['sometimes', 'nullable', 'string', 'max:100'],
            'email'         => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password'      => ['sometimes', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'is_active'     => ['sometimes', 'boolean'],
            'phone'         => ['sometimes', 'nullable', 'string', 'max:30'],
            'department'    => ['sometimes', 'nullable', 'string', 'max:100'],
            'roles'         => ['sometimes', 'array'],
            'roles.*'       => ['string', 'exists:roles,name'],
            'permissions'   => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->hasAny(['first_name', 'middle_name', 'last_name'])) {
            $name = trim(implode(' ', array_filter([
                trim((string) $this->input('first_name', '')),
                trim((string) $this->input('middle_name', '')),
                trim((string) $this->input('last_name', '')),
            ])));

            if ($name !== '') {
                $this->merge(['name' => $name]);
            }
        }

        if ($this->has('email')) {
            $this->merge([
                'email' => strtolower(trim((string) $this->input('email', ''))),
            ]);
        }
    }
}
