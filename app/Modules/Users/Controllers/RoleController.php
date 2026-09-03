<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Users\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Full CRUD for Spatie Roles, including permission syncing.
 *
 * Routes are protected by operation-level role permissions.
 *
 * Super Admin is protected from modification and receives permissions via
 * RolesAndPermissionsSeeder.
 */
class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:role-view', only: ['index', 'show']),
            new Middleware('permission:role-create', only: ['store']),
            new Middleware('permission:role-edit', only: ['update']),
            new Middleware('permission:role-delete', only: ['destroy', 'forceDelete']),
            new Middleware('permission:role-restore', only: ['restore']),
        ];
    }

    /**
     * List all roles with their permission counts.
     * Supports search by name and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('role-view'), 403);

        $sortMap = [
            'name' => 'name',
            'guard' => 'guard_name',
            'permissions' => 'permissions_count',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $sortBy = $sortMap[$request->input('sort_by', 'name')] ?? 'name';
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->input('per_page', 15), 1), 500);
        $deletedFilter = $request->input('deleted');

        $roles = Role::query()
            ->when($deletedFilter === 'with', fn ($q) => $q->withTrashed())
            ->when($deletedFilter === 'only', fn ($q) => $q->onlyTrashed())
            ->withCount('permissions')
            ->with('permissions')
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('name', 'like', '%'.$request->input('search').'%'),
            )
            ->when(
                $request->filled('guard_name'),
                fn ($q) => $q->where('guard_name', $request->input('guard_name')),
            )
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);

        return response()->json($roles);
    }

    /**
     * Create a new role and optionally assign permissions to it.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => $request->validated('guard_name', 'web'),
            'team_id' => null, // Force global role creation regardless of active session team context
        ]);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->validated('permissions'));
        }

        $role->load('permissions');

        return response()->json([
            'message' => "Role [{$role->name}] created successfully.",
            'data' => $role,
        ], 201);
    }

    /**
     * Show a single role with all its permissions.
     */
    public function show(Request $request, int|string $role): JsonResponse
    {
        abort_unless($request->user()?->can('role-view'), 403);

        $role = Role::withTrashed()
            ->with('permissions')
            ->withCount('permissions')
            ->findOrFail($role);

        return response()->json([
            'data' => $role,
        ]);
    }

    /**
     * Update the role name and replace its permissions entirely.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->guardSystemRole($role);

        $role->update([
            'name' => $request->validated('name'),
            'guard_name' => $request->validated('guard_name', $role->guard_name),
            'team_id' => null, // Force global role update regardless of active session team context
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->validated('permissions', []));
        }

        $role->load('permissions');

        return response()->json([
            'message' => "Role [{$role->name}] updated successfully.",
            'data' => $role,
        ]);
    }

    /**
     * Delete a role. Prevents deletion of system-critical roles.
     */
    public function destroy(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()?->can('role-delete'), 403);

        $this->guardSystemRole($role);

        $name = $role->name;
        $role->delete();

        return response()->json([
            'message' => "Role [{$name}] temporarily deleted successfully.",
        ]);
    }

    public function restore(Request $request, int|string $role): JsonResponse
    {
        abort_unless($request->user()?->can('role-restore'), 403);

        $role = Role::withTrashed()
            ->with('permissions')
            ->findOrFail($role);

        if (! $role->trashed()) {
            return response()->json([
                'message' => "Role [{$role->name}] is not deleted.",
                'data' => $role,
            ]);
        }

        $role->restore();
        $role->load('permissions');

        return response()->json([
            'message' => "Role [{$role->name}] restored successfully.",
            'data' => $role,
        ]);
    }

    public function forceDelete(Request $request, int|string $role): JsonResponse
    {
        abort_unless($request->user()?->can('role-permanent-delete'), 403);

        $role = Role::withTrashed()->findOrFail($role);
        $this->guardSystemRole($role);

        $name = $role->name;
        $role->forceDelete();

        return response()->json([
            'message' => "Role [{$name}] permanently deleted successfully.",
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        abort_unless(
            $request->user()?->can('role-view')
            || $request->user()?->can('user-create')
            || $request->user()?->can('user-edit')
            || $request->user()?->can('role-create')
            || $request->user()?->can('role-edit'),
            403,
        );

        return response()->json(Role::query()
            ->orderBy('name')
            ->get(['id', 'name', 'guard_name']));
    }

    // ─── Private ──────────────────────────────────────────────────────────────

    /**
     * Abort if the role is a protected system role.
     */
    private function guardSystemRole(Role $role): void
    {
        $protected = ['Super Admin'];

        if (in_array($role->name, $protected, strict: true)) {
            abort(403, "The [{$role->name}] role is system-critical and cannot be modified or deleted.");
        }
    }
}
