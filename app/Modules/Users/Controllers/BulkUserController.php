<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Bulk operations on a set of user IDs.
 *
 * POST /api/users/bulk-action
 * Body: { action: 'activate'|'deactivate'|'delete'|'restore'|'force-delete', ids: [1,2,3] }
 */
class BulkUserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete,restore,force-delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
        ]);

        $ids = $validated['ids'];
        $action = $validated['action'];
        $ability = match ($action) {
            'activate', 'deactivate' => 'user-activate',
            'delete' => 'user-delete',
            'restore' => 'user-restore',
            'force-delete' => 'user-permanent-delete',
        };

        abort_unless($request->user()?->can($ability), 403);

        // Gracefully skip the Master Admin (ID 1) from any bulk action
        $ids = array_values(array_filter($ids, fn ($id) => (int) $id !== 1));

        if (empty($ids)) {
            return response()->json(['message' => 'No valid users selected (Master Admin is protected).'], 422);
        }

        // Prevent unauthorized modification of Super Admins
        $superAdminIds = User::role('Super Admin')->pluck('id')->toArray();
        $overlapping = array_intersect($ids, $superAdminIds);

        if (! empty($overlapping)) {
            if (! $request->user()?->hasRole('Super Admin')) {
                return response()->json(['message' => 'You cannot modify Super Admin users.'], 403);
            }

            if (($action === 'delete' || $action === 'force-delete') && count($superAdminIds) <= count($overlapping)) {
                return response()->json([
                    'message' => $action === 'force-delete'
                        ? 'Cannot permanently delete the last Super Admin user.'
                        : 'Cannot delete the last Super Admin user.',
                ], 403);
            }
        }

        if ($action === 'restore') {
            User::withTrashed()->whereIn('id', $ids)->restore();

            return response()->json([
                'message' => count($ids).' user(s) restored successfully.',
                'ids' => $ids,
            ]);
        }

        if ($action === 'delete') {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $ids)
                ->delete();

            DB::table('sessions')
                ->whereIn('user_id', $ids)
                ->delete();

            User::whereIn('id', $ids)->delete();

            return response()->json([
                'message' => count($ids).' user(s) deleted successfully.',
                'deleted' => $ids,
            ]);
        }

        if ($action === 'force-delete') {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $ids)
                ->delete();

            DB::table('sessions')
                ->whereIn('user_id', $ids)
                ->delete();

            User::withTrashed()->whereIn('id', $ids)->forceDelete();

            return response()->json([
                'message' => count($ids).' user(s) permanently deleted successfully.',
                'deleted' => $ids,
            ]);
        }

        $isActive = $action === 'activate';

        User::whereIn('id', $ids)->update(['is_active' => $isActive]);

        if (! $isActive) {
            DB::table('personal_access_tokens')
                ->where('tokenable_type', User::class)
                ->whereIn('tokenable_id', $ids)
                ->delete();

            DB::table('sessions')
                ->whereIn('user_id', $ids)
                ->delete();
        }

        return response()->json([
            'message' => count($ids).' user(s) '.($isActive ? 'activated' : 'deactivated').' successfully.',
            'is_active' => $isActive,
            'ids' => $ids,
        ]);
    }
}
