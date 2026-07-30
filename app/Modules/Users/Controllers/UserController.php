<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Full CRUD for Users, including role/permission syncing, account activation
 * toggle, and login history access.
 *
 * Routes are protected by operation-level permissions such as user-view,
 * user-create, user-edit, user-delete, and user-restore.
 */
class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:user-view', only: ['index', 'show', 'loginHistory']),
            new Middleware('permission:user-create', only: ['store']),
            new Middleware('permission:user-edit', only: ['update', 'toggleActive', 'syncRoles', 'syncPermissions']),
            new Middleware('permission:user-delete', only: ['destroy', 'forceDelete']),
            new Middleware('permission:user-restore', only: ['restore']),
        ];
    }

    /**
     * List users with pagination, search, and optional role filtering.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('user-view'), 403);

        $sortMap = [
            'name' => 'name',
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'email' => 'email',
            'lastActive' => 'updated_at',
            'joinDate' => 'created_at',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $sortBy = $sortMap[$request->input('sort_by', 'name')] ?? 'name';
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $deletedFilter = $request->input('deleted');

        $activeSessionUserIds = DB::table('sessions')
            ->where('last_activity', '>=', now()->subMinutes(15)->getTimestamp())
            ->pluck('user_id')
            ->filter()
            ->toArray();

        $activeApiUserIds = DB::table('personal_access_tokens')
            ->where('tokenable_type', User::class)
            ->where('last_used_at', '>=', now()->subMinutes(15))
            ->pluck('tokenable_id')
            ->filter()
            ->toArray();

        $allActiveUserIds = array_map('intval', array_unique(array_merge($activeSessionUserIds, $activeApiUserIds)));
        $activeUserIdsString = empty($allActiveUserIds) ? '0' : implode(',', $allActiveUserIds);

        $users = User::query()
            ->select('users.*')
            ->when($deletedFilter === 'with', fn ($q) => $q->withTrashed())
            ->when($deletedFilter === 'only', fn ($q) => $q->onlyTrashed())
            ->with(['roles', 'permissions'])
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($inner) use ($request): void {
                    $term = '%'.$request->input('search').'%';
                    $inner->where('users.name', 'like', $term)
                        ->orWhere('users.first_name', 'like', $term)
                        ->orWhere('users.middle_name', 'like', $term)
                        ->orWhere('users.last_name', 'like', $term)
                        ->orWhere('users.email', 'like', $term)
                        ->orWhere('users.phone', 'like', $term)
                        ->orWhere('users.department', 'like', $term);
                }),
            )
            ->when(
                $request->filled('role'),
                fn ($q) => $q->role($request->input('role')),
            )
            ->when(
                $request->has('is_active'),
                fn ($q) => $q->where('users.is_active', (bool) $request->input('is_active')),
            )
            ->orderByRaw("users.id IN ($activeUserIdsString) DESC")
            ->orderBy(in_array($sortBy, ['name', 'first_name', 'last_name', 'email', 'created_at', 'updated_at']) ? "users.{$sortBy}" : $sortBy, $sortDir)
            ->paginate($perPage);

        $userIds = $users->getCollection()->pluck('id')->toArray();

        $latestLoginHistories = DB::table('login_histories')
            ->whereIn('id', function ($q) use ($userIds) {
                $q->select(DB::raw('MAX(id)'))
                    ->from('login_histories')
                    ->whereIn('user_id', $userIds)
                    ->where('status', 'success')
                    ->groupBy('user_id');
            })
            ->get()
            ->keyBy('user_id');

        $users->getCollection()->transform(function ($user) use ($allActiveUserIds, $latestLoginHistories) {
            $user->is_online = in_array($user->id, $allActiveUserIds);

            $latestLogin = $latestLoginHistories[$user->id] ?? null;
            $user->last_login_at = $latestLogin ? $latestLogin->attempted_at : null;
            $user->device_type = $latestLogin ? ucfirst($latestLogin->device_type) : 'Web';

            return $user;
        });

        return response()->json($users);
    }

    /**
     * Create a new user with optional roles and direct permissions.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'first_name' => $validated['first_name'] ?? null,
            'middle_name' => $validated['middle_name'] ?? null,
            'last_name' => $validated['last_name'] ?? null,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
            'phone' => $validated['phone'] ?? null,
            'department' => $validated['department'] ?? null,
            'employee_id' => $validated['employee_id'] ?? null,
            'photo' => $validated['photo'] ?? null,
            'joining_date' => $validated['joining_date'] ?? null,
            'address_line_1' => $validated['address_line_1'] ?? null,
            'address_line_2' => $validated['address_line_2'] ?? null,
            'village_id' => $validated['village_id'] ?? null,
            'village_name' => $validated['village_name'] ?? null,
            'post_office' => $validated['post_office'] ?? null,
            'taluka' => $validated['taluka'] ?? null,
            'district' => $validated['district'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
        ]);

        if ($request->hasFile('photo_file')) {
            $file = $request->file('photo_file');
            $extension = $file->extension() ?: 'jpg';
            $filename = 'user-'.$user->id.'-'.time().'.'.$extension;
            $user->photo = asset('storage/'.$file->storeAs('users/photos', $filename, 'public'));
            $user->saveQuietly();
        }

        if (! empty($validated['roles'])) {
            abort_unless($request->user()?->can('user-sync-roles'), 403, 'You do not have permission to sync roles.');
            if (in_array('Super Admin', $validated['roles']) && ! $request->user()?->hasRole('Super Admin')) {
                return response()->json(['message' => 'You cannot assign the Super Admin role without being a Super Admin.'], 403);
            }
            $user->syncRoles($validated['roles']);
        }

        if (! empty($validated['permissions'])) {
            abort_unless($request->user()?->can('user-sync-permissions'), 403, 'You do not have permission to sync permissions.');
            $user->syncPermissions($validated['permissions']);
        }

        $user->load(['roles', 'permissions']);

        return response()->json([
            'message' => "User [{$user->email}] created successfully.",
            'data' => $user,
        ], 201);
    }

    /**
     * Show a single user with their roles, permissions, and recent login history.
     */
    public function show(Request $request, int|string $user): JsonResponse
    {
        abort_unless($request->user()?->can('user-view'), 403);

        $user = User::withTrashed()
            ->with(['roles', 'permissions'])
            ->findOrFail($user);

        $loginHistory = $user->loginHistories()
            ->orderByDesc('attempted_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $user,
            'login_history' => $loginHistory,
        ]);
    }

    /**
     * Update user fields, roles, and direct permissions.
     * Only provided fields are updated (PATCH semantics).
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        if ($user->hasRole('Super Admin') && ! $request->user()?->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot modify a Super Admin user.'], 403);
        }

        $validated = $request->validated();

        $fillable = [];
        $allowedFields = [
            'name', 'first_name', 'middle_name', 'last_name', 'email', 'is_active', 'phone',
            'department', 'employee_id', 'photo', 'joining_date',
            'address_line_1', 'address_line_2', 'village_id', 'village_name', 'post_office',
            'taluka', 'district', 'city', 'state', 'pincode',
        ];

        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $validated)) {
                $fillable[$field] = $validated[$field];
            }
        }
        if (array_key_exists('password', $validated)) {
            $fillable['password'] = Hash::make($validated['password']);
        }

        if ($request->hasFile('photo_file')) {
            $file = $request->file('photo_file');
            $extension = $file->extension() ?: 'jpg';
            $filename = 'user-'.$user->id.'-'.time().'.'.$extension;
            $fillable['photo'] = asset('storage/'.$file->storeAs('users/photos', $filename, 'public'));
        }

        if (! empty($fillable)) {
            $user->update($fillable);
        }

        if (array_key_exists('roles', $validated)) {
            abort_unless($request->user()?->can('user-sync-roles'), 403, 'You do not have permission to sync roles.');
            if (in_array('Super Admin', $validated['roles']) && ! $request->user()?->hasRole('Super Admin')) {
                return response()->json(['message' => 'You cannot assign the Super Admin role without being a Super Admin.'], 403);
            }
            $user->syncRoles($validated['roles']);
        }

        if (array_key_exists('permissions', $validated)) {
            abort_unless($request->user()?->can('user-sync-permissions'), 403, 'You do not have permission to sync permissions.');
            $user->syncPermissions($validated['permissions']);
        }

        $user->load(['roles', 'permissions']);

        return response()->json([
            'message' => "User [{$user->email}] updated successfully.",
            'data' => $user,
        ]);
    }

    /**
     * Temporarily delete a user and revoke all their Sanctum tokens.
     * Prevents deletion of the last Super Admin.
     */
    public function destroy(Request $request, int|string $user): JsonResponse
    {
        abort_unless($request->user()?->can('user-delete'), 403);

        $user = User::withTrashed()
            ->with('roles')
            ->findOrFail($user);

        if ($user->trashed()) {
            return response()->json([
                'message' => "User [{$user->email}] is already temporarily deleted.",
            ]);
        }

        if ($user->hasRole('Super Admin')) {
            if (! $request->user()?->hasRole('Super Admin')) {
                return response()->json(['message' => 'You cannot delete a Super Admin user.'], 403);
            }
            $superAdminCount = User::role('Super Admin')->count();
            if ($superAdminCount <= 1) {
                return response()->json([
                    'message' => 'Cannot delete the last Super Admin user.',
                ], 403);
            }
        }

        // Revoke all active tokens before deleting
        $user->tokens()->delete();

        $email = $user->email;
        $user->delete();

        return response()->json([
            'message' => "User [{$email}] temporarily deleted successfully.",
        ]);
    }

    /**
     * Restore a temporarily deleted user.
     */
    public function restore(Request $request, int|string $user): JsonResponse
    {
        abort_unless($request->user()?->can('user-restore'), 403);

        $user = User::withTrashed()
            ->with(['roles', 'permissions'])
            ->findOrFail($user);

        if (! $user->trashed()) {
            return response()->json([
                'message' => "User [{$user->email}] is not deleted.",
                'data' => $user,
            ]);
        }

        $user->restore();
        $user->load(['roles', 'permissions']);

        return response()->json([
            'message' => "User [{$user->email}] restored successfully.",
            'data' => $user,
        ]);
    }

    /**
     * Permanently delete a user.
     */
    public function forceDelete(Request $request, int|string $user): JsonResponse
    {
        abort_unless($request->user()?->can('user-permanent-delete'), 403);

        $user = User::withTrashed()
            ->with('roles')
            ->findOrFail($user);

        if ($user->hasRole('Super Admin')) {
            if (! $request->user()?->hasRole('Super Admin')) {
                return response()->json(['message' => 'You cannot permanently delete a Super Admin user.'], 403);
            }
            $superAdminCount = User::role('Super Admin')->count();
            if (! $user->trashed() && $superAdminCount <= 1) {
                return response()->json([
                    'message' => 'Cannot permanently delete the last Super Admin user.',
                ], 403);
            }
        }

        $email = $user->email;
        $user->tokens()->delete();
        $user->forceDelete();

        return response()->json([
            'message' => "User [{$email}] permanently deleted successfully.",
        ]);
    }

    // ─── Specialised Actions ──────────────────────────────────────────────────

    /**
     * Toggle the user's is_active status.
     * Automatically revokes all tokens when deactivating.
     */
    public function toggleActive(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->can('user-activate'), 403);

        if ($user->hasRole('Super Admin') && ! $request->user()?->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot modify a Super Admin user.'], 403);
        }

        $newState = ! $user->is_active;

        $user->update(['is_active' => $newState]);

        // Revoke all active API tokens when deactivating the account
        if (! $newState) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'User account '.($newState ? 'activated' : 'deactivated').'.',
            'is_active' => $newState,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Replace all roles on the user in a single operation.
     */
    public function syncRoles(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->can('user-sync-roles'), 403);

        $request->validate([
            'roles' => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $rolesToAssign = $request->input('roles');
        if (in_array('Super Admin', $rolesToAssign) && ! $request->user()?->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot assign the Super Admin role without being a Super Admin.'], 403);
        }

        $user->syncRoles($rolesToAssign);
        $user->load('roles');

        return response()->json([
            'message' => "Roles synced for user [{$user->email}].",
            'data' => $user->roles,
        ]);
    }

    /**
     * Replace all direct permissions on the user in a single operation.
     *
     * Note: This does not affect permissions inherited via roles.
     */
    public function syncPermissions(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->can('user-sync-permissions'), 403);

        if ($user->hasRole('Super Admin') && ! $request->user()?->hasRole('Super Admin')) {
            return response()->json(['message' => 'You cannot modify a Super Admin user.'], 403);
        }

        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->syncPermissions($request->input('permissions'));
        $user->load('permissions');

        return response()->json([
            'message' => "Direct permissions synced for user [{$user->email}].",
            'data' => $user->permissions,
        ]);
    }

    /**
     * Return the full login history for a user (paginated).
     * Useful for the audit log screen in the admin panel.
     */
    public function loginHistory(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()?->can('audit-log-view'), 403);

        $history = $user->loginHistories()
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status')),
            )
            ->orderByDesc('attempted_at')
            ->paginate((int) $request->input('per_page', 25));

        return response()->json($history);
    }
}
