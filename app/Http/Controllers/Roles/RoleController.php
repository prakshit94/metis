<?php

declare(strict_types=1);

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\StoreRoleRequest;
use App\Http\Requests\Roles\UpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

/**
 * Full CRUD for Spatie Roles, including permission syncing.
 *
 * All routes are protected at the route level via:
 *   middleware('permission:manage-roles')
 *
 * The Super Admin bypass is enforced globally via Gate::before in
 * AppServiceProvider and therefore applies automatically here.
 */
class RoleController extends Controller
{
    /**
     * List all roles with their permission counts.
     * Supports search by name and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $roles = Role::query()
            ->withCount('permissions')
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('name', 'like', '%' . $request->input('search') . '%'),
            )
            ->when(
                $request->filled('guard_name'),
                fn ($q) => $q->where('guard_name', $request->input('guard_name')),
            )
            ->orderBy($request->input('sort_by', 'name'), $request->input('sort_dir', 'asc'))
            ->paginate((int) $request->input('per_page', 15));

        return response()->json($roles);
    }

    /**
     * Create a new role and optionally assign permissions to it.
     */
    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name'       => $request->validated('name'),
            'guard_name' => $request->validated('guard_name', 'web'),
        ]);

        if ($request->filled('permissions')) {
            $role->syncPermissions($request->validated('permissions'));
        }

        $role->load('permissions');

        return response()->json([
            'message' => "Role [{$role->name}] created successfully.",
            'data'    => $role,
        ], 201);
    }

    /**
     * Show a single role with all its permissions.
     */
    public function show(Role $role): JsonResponse
    {
        return response()->json([
            'data' => $role->load('permissions'),
        ]);
    }

    /**
     * Update the role name and replace its permissions entirely.
     */
    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $this->guardSystemRole($role);

        $role->update([
            'name'       => $request->validated('name'),
            'guard_name' => $request->validated('guard_name', $role->guard_name),
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->validated('permissions', []));
        }

        $role->load('permissions');

        return response()->json([
            'message' => "Role [{$role->name}] updated successfully.",
            'data'    => $role,
        ]);
    }

    /**
     * Delete a role. Prevents deletion of system-critical roles.
     */
    public function destroy(Role $role): JsonResponse
    {
        $this->guardSystemRole($role);

        $name = $role->name;
        $role->delete();

        return response()->json([
            'message' => "Role [{$name}] deleted successfully.",
        ]);
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
