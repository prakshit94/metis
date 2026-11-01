<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;

class HierarchyGuard
{
    public static function validate(string $class, ?int $id, ?int $parentId): void
    {
        $seen = [];
        while ($parentId !== null) {
            if ($parentId === $id || isset($seen[$parentId])) {
                throw ValidationException::withMessages(['parent_id' => 'The selected parent would create a hierarchy cycle.']);
            }
            $seen[$parentId] = true;
            $parent = $class::find($parentId);
            if (! $parent) {
                throw ValidationException::withMessages(['parent_id' => 'The selected parent does not exist.']);
            }
            $parentId = $parent->parent_id === null ? null : (int) $parent->parent_id;
        }
    }
}
