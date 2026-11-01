<?php

namespace App\Support;

final class HrPermissions
{
    // Each action previously used the permission on the right.
    public const LEGACY = [
        'attendance-export' => 'attendance-view',
        'attendance-restore' => 'attendance-edit',
        'attendance-permanent-delete' => 'attendance-delete',
        'attendance-correction-review' => 'attendance-edit',
        'attendance-roster-import' => 'attendance-edit',
        'attendance-co-review' => 'attendance-edit',
        'leave-export' => 'leave-view',
        'leave-approve' => 'leave-edit',
    ];

    public static function expand(array $permissions): array
    {
        foreach (self::LEGACY as $action => $legacy) {
            if (in_array($legacy, $permissions, true)) {
                $permissions[] = $action;
            }
        }

        return array_values(array_unique($permissions));
    }
}
