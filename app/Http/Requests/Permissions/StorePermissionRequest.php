<?php

declare(strict_types=1);

namespace App\Http\Requests\Permissions;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('permission-create') ?? false;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100', Rule::unique('permissions', 'name')],
            'guard_name' => ['sometimes', 'string', 'max:100'],
        ];
    }
}
