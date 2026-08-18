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
        if (! $this->user()) {
            return false;
        }
        
        if ($this->user()->can('user-edit')) {
            return true;
        }

        $routeUser = $this->route('user');
        $targetUserId = is_object($routeUser) ? $routeUser->id : (int) $routeUser;

        return $this->user()->id === $targetUserId;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $routeUser = $this->route('user');
        $userId = is_object($routeUser) ? $routeUser->id : (int) $routeUser;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'first_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['sometimes', 'confirmed', Password::min(8)->mixedCase()->numbers()],
            'is_active' => ['sometimes', 'boolean'],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^\d{10}$/'],
            'department_id' => ['sometimes', 'nullable', 'exists:departments,id'],
            'manager_id' => ['sometimes', 'nullable', 'exists:users,id'],
            'employment_type' => ['sometimes', 'string', 'in:Full-time,Part-time,Contract,Intern'],
            'employee_id' => [
                'sometimes',
                'nullable',
                'string',
                'max:100',
                Rule::unique('users', 'employee_id')->ignore($userId),
            ],
            'photo' => ['sometimes', 'nullable', 'string'],
            'photo_file' => ['sometimes', 'nullable', 'image', 'max:2048'],
            'joining_date' => ['sometimes', 'nullable', 'date'],
            'address_line_1' => ['sometimes', 'nullable', 'string', 'max:255'],
            'address_line_2' => ['sometimes', 'nullable', 'string', 'max:255'],
            'village_id' => ['sometimes', 'nullable', 'integer'],
            'village_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'post_office' => ['sometimes', 'nullable', 'string', 'max:255'],
            'taluka' => ['sometimes', 'nullable', 'string', 'max:255'],
            'district' => ['sometimes', 'nullable', 'string', 'max:255'],
            'city' => ['sometimes', 'nullable', 'string', 'max:255'],
            'state' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pincode' => ['sometimes', 'nullable', 'string', 'max:20'],
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
            'date_of_birth' => ['sometimes', 'nullable', 'date'],
            'gender' => ['sometimes', 'nullable', 'string', 'in:Male,Female,Other,Prefer not to say'],
            'blood_group' => ['sometimes', 'nullable', 'string', 'max:10'],
            'designation' => ['sometimes', 'nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['sometimes', 'nullable', 'string', 'regex:/^\d{10}$/'],
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
