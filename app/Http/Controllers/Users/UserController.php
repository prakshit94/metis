<?php

declare(strict_types=1);

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\StoreUserRequest;
use App\Http\Requests\Users\UpdateUserRequest;
use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * Full CRUD for Users, including role/permission syncing, account activation
 * toggle, and login history access.
 *
 * Routes are protected at the route level via:
 *   middleware('permission:manage-users')
 */
class UserController extends Controller
{
    /**
     * List users with pagination, search, and optional role filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->with(['roles', 'permissions'])
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($inner) use ($request): void {
                    $term = '%' . $request->input('search') . '%';
                    $inner->where('name', 'like', $term)
                        ->orWhere('email', 'like', $term);
                }),
            )
            ->when(
                $request->filled('role'),
                fn ($q) => $q->role($request->input('role')),
            )
            ->when(
                $request->has('is_active'),
                fn ($q) => $q->where('is_active', (bool) $request->input('is_active')),
            )
            ->orderBy($request->input('sort_by', 'name'), $request->input('sort_dir', 'asc'))
            ->paginate((int) $request->input('per_page', 15));

        return response()->json($users);
    }

    /**
     * Create a new user with optional roles and direct permissions.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name'      => $validated['name'],
            'email'     => $validated['email'],
            'password'  => Hash::make($validated['password']),
            'is_active' => $validated['is_active'] ?? true,
        ]);

        if (! empty($validated['roles'])) {
            $user->syncRoles($validated['roles']);
        }

        if (! empty($validated['permissions'])) {
            $user->syncPermissions($validated['permissions']);
        }

        $user->load(['roles', 'permissions']);

        return response()->json([
            'message' => "User [{$user->email}] created successfully.",
            'data'    => $user,
        ], 201);
    }

    /**
     * Show a single user with their roles, permissions, and recent login history.
     */
    public function show(User $user): JsonResponse
    {
        $user->load(['roles', 'permissions']);

        $loginHistory = $user->loginHistories()
            ->orderByDesc('attempted_at')
            ->limit(20)
            ->get();

        return response()->json([
            'data'          => $user,
            'login_history' => $loginHistory,
        ]);
    }

    /**
     * Update user fields, roles, and direct permissions.
     * Only provided fields are updated (PATCH semantics).
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        $fillable = array_filter([
            'name'      => $validated['name'] ?? null,
            'email'     => $validated['email'] ?? null,
            'is_active' => $validated['is_active'] ?? null,
            'password'  => isset($validated['password']) ? Hash::make($validated['password']) : null,
        ], fn ($v) => $v !== null);

        $user->update($fillable);

        if (array_key_exists('roles', $validated)) {
            $user->syncRoles($validated['roles']);
        }

        if (array_key_exists('permissions', $validated)) {
            $user->syncPermissions($validated['permissions']);
        }

        $user->load(['roles', 'permissions']);

        return response()->json([
            'message' => "User [{$user->email}] updated successfully.",
            'data'    => $user,
        ]);
    }

    /**
     * Delete a user and revoke all their Sanctum tokens.
     * Prevents deletion of the last Super Admin.
     */
    public function destroy(User $user): JsonResponse
    {
        if ($user->hasRole('Super Admin')) {
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
            'message' => "User [{$email}] deleted successfully.",
        ]);
    }

    // ─── Specialised Actions ──────────────────────────────────────────────────

    /**
     * Toggle the user's is_active status.
     * Automatically revokes all tokens when deactivating.
     */
    public function toggleActive(User $user): JsonResponse
    {
        $newState = ! $user->is_active;

        $user->update(['is_active' => $newState]);

        // Revoke all active API tokens when deactivating the account
        if (! $newState) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message'   => "User account " . ($newState ? 'activated' : 'deactivated') . ".",
            'is_active' => $newState,
            'user_id'   => $user->id,
        ]);
    }

    /**
     * Replace all roles on the user in a single operation.
     */
    public function syncRoles(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'roles'   => ['required', 'array'],
            'roles.*' => ['string', 'exists:roles,name'],
        ]);

        $user->syncRoles($request->input('roles'));
        $user->load('roles');

        return response()->json([
            'message' => "Roles synced for user [{$user->email}].",
            'data'    => $user->roles,
        ]);
    }

    /**
     * Replace all direct permissions on the user in a single operation.
     *
     * Note: This does not affect permissions inherited via roles.
     */
    public function syncPermissions(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'permissions'   => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $user->syncPermissions($request->input('permissions'));
        $user->load('permissions');

        return response()->json([
            'message' => "Direct permissions synced for user [{$user->email}].",
            'data'    => $user->permissions,
        ]);
    }

    /**
     * Return the full login history for a user (paginated).
     * Useful for the audit log screen in the admin panel.
     */
    public function loginHistory(Request $request, User $user): JsonResponse
    {
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
