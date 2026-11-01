<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Apply the directory's existing LOB boundary to individual and bulk requests. */
final class UserDirectoryAccess
{
    public function handle(Request $request, \Closure $next)
    {
        $actor = $request->user();
        $teamId = $actor?->lob_team_id;
        $originalTeam = getPermissionsTeamId();
        try {
            if ($teamId !== null) {
                $target = $request->route('user');
                if ($target !== null) {
                    $id = $target instanceof User ? $target->id : $target;
                    $this->assertIds([$id], $teamId, $actor);
                }
                if (is_array($request->input('ids'))) {
                    $ids = $request->input('ids');
                    abort_if(count($ids) > 100 || count(array_filter($ids, 'is_scalar')) !== count($ids), 422, 'Select up to 100 user IDs.');
                    $this->assertIds($ids, $teamId, $actor);
                }
                if (! $request->isMethod('GET')) {
                    if ($request->has('team_id')) {
                        abort_unless((string) $request->input('team_id') === (string) $teamId, 403, 'Users must remain in your permitted LOB.');
                    } elseif ($request->route()->getActionMethod() === 'store') {
                        $request->merge(['team_id' => $teamId]);
                    }
                    if ($request->filled('manager_id')) {
                        $this->assertIds([$request->input('manager_id')], $teamId, $actor);
                    }
                }
            }

            return $next($request);
        } finally {
            // Role assignment must never leak its target team into the caller's context.
            setPermissionsTeamId($originalTeam);
        }
    }

    /** Shared lock order prevents concurrent requests removing the remaining administrator. */
    public function lockAdministrators(): void
    {
        User::withTrashed()->whereHas('allRoles', fn ($query) => $query->where('name', 'Super Admin'))
            ->orderBy('id')->lockForUpdate()->get(['users.id']);
    }

    private function assertIds(array $ids, int $teamId, User $actor): void
    {
        $ids = array_unique(array_map('strval', $ids));
        $allowed = DB::table(config('permission.table_names.model_has_roles'))
            ->where('team_id', $teamId)->where('model_type', User::class)
            ->whereIn('model_id', $ids)->pluck('model_id')->map(fn ($id) => (string) $id)->all();
        $allowed[] = (string) $actor->id;
        abort_if(array_diff($ids, $allowed) !== [], 404);
    }
}
