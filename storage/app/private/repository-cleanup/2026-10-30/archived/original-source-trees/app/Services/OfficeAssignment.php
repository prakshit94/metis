<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Users\Models\Office;
use App\Modules\Users\Models\Team;
use App\Modules\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class OfficeAssignment
{
    /** Called inside the user mutation transaction. Existing assignments are not silently moved. */
    public function validate(Request $request, ?User $target = null): ?int
    {
        $currentTeam = $target?->allRoles()->whereNotNull('model_has_roles.team_id')->value('model_has_roles.team_id');
        $teamId = $request->has('team_id') && $request->has('roles') ? $request->input('team_id') : $currentTeam;
        $officeId = $request->has('office_id') ? ($request->input('office_id') ?: null) : $target?->office_id;
        $officeChanged = (string)$officeId !== (string)$target?->office_id;
        if ($officeChanged) abort_unless($request->user()?->can('user-sync-roles'), 403, 'Office assignments require role-assignment permission.');
        $moving = (string)$teamId !== (string)$currentTeam;
        if ($teamId) {
            $team = Team::whereKey($teamId)->lockForUpdate()->firstOrFail();
            if (($moving || !$target) && (!$team->is_active || !$team->resolved_state_name)) {
                throw ValidationException::withMessages(['team_id' => 'Select an active state team with a valid state mapping.']);
            }
        }
        if ($officeId) {
            $office = Office::whereKey($officeId)->lockForUpdate()->first();
            if (!$office || !$teamId || (int)$office->team_id !== (int)$teamId) {
                throw ValidationException::withMessages(['office_id' => 'Select an office within the assigned state, or clear the office before moving states.']);
            }
            if (($officeChanged || $moving || !$target) && (!$office->is_active || !$team->is_active)) {
                throw ValidationException::withMessages(['office_id' => 'New assignments require an active office and state.']);
            }
        }
        return $officeId ? (int)$officeId : null;
    }
}
