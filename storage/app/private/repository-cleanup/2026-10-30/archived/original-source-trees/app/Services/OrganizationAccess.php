<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Users\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class OrganizationAccess
{
    public function assertTeam(Request $request, int $teamId): void
    {
        $scope = $request->user()?->lob_team_id;
        abort_if($scope !== null && $scope !== $teamId, 404);
    }

    public function dependencies(Team $team): array
    {
        return [
            'members' => $team->members()->withTrashed()->distinct()->count('users.id'),
            'role_assignments' => DB::table('model_has_roles')->where('team_id', $team->id)->count(),
            'permission_assignments' => DB::table('model_has_permissions')->where('team_id', $team->id)->count(),
            'scoped_roles' => DB::table('roles')->where('team_id', $team->id)->count(),
            'targets' => $team->targets()->count(),
            'offices' => $team->offices()->count(),
            'orders' => DB::table('orders')->where(fn ($q) => $q->where('state_team_id', $team->id)
                ->when($team->code, fn ($old) => $old->orWhere('order_no', 'like', $team->code.'%')))->count(),
        ];
    }

    public function audit(Request $request, string $type, int $id, string $event, ?array $before, ?array $after): void
    {
        DB::table('organization_events')->insert(['actor_id' => $request->user()?->id,
            'entity_type' => $type, 'entity_id' => $id, 'event' => $event,
            'before' => $before === null ? null : json_encode($before, JSON_THROW_ON_ERROR),
            'after' => $after === null ? null : json_encode($after, JSON_THROW_ON_ERROR), 'created_at' => now()]);
    }
}
