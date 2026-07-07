<?php

declare(strict_types=1);

namespace App\Http\Controllers\Roles;

use App\Http\Controllers\Controller;
use App\Http\Requests\Permissions\StorePermissionRequest;
use App\Http\Requests\Permissions\UpdatePermissionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

/**
 * Full CRUD for Spatie Permissions.
 *
 * Routes are protected at the route level via:
 *   middleware('permission:manage-roles')
 */
class PermissionController extends Controller
{
    /**
     * List all permissions with pagination and optional search.
     */
    public function index(Request $request): JsonResponse
    {
        $permissions = Permission::query()
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

        return response()->json($permissions);
    }

    /**
     * Create a new permission.
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = Permission::create([
            'name'       => $request->validated('name'),
            'guard_name' => $request->validated('guard_name', 'web'),
        ]);

        return response()->json([
            'message' => "Permission [{$permission->name}] created successfully.",
            'data'    => $permission,
        ], 201);
    }

    /**
     * Show a single permission with all roles that have it.
     */
    public function show(Permission $permission): JsonResponse
    {
        return response()->json([
            'data' => $permission->load('roles'),
        ]);
    }

    /**
     * Update a permission's name.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission->update([
            'name'       => $request->validated('name'),
            'guard_name' => $request->validated('guard_name', $permission->guard_name),
        ]);

        return response()->json([
            'message' => "Permission [{$permission->name}] updated successfully.",
            'data'    => $permission,
        ]);
    }

    /**
     * Delete a permission.
     *
     * Spatie automatically detaches the permission from all roles and models
     * via DB cascade constraints defined in the migration.
     */
    public function destroy(Permission $permission): JsonResponse
    {
        $name = $permission->name;
        $permission->delete();

        return response()->json([
            'message' => "Permission [{$name}] deleted successfully.",
        ]);
    }
}
