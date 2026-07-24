<?php

declare(strict_types=1);

namespace App\Modules\Users\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Http\Requests\Permissions\StorePermissionRequest;
use App\Http\Requests\Permissions\UpdatePermissionRequest;
use App\Modules\Users\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Full CRUD for Spatie Permissions.
 *
 * Routes are protected by operation-level permission permissions.
 */
class PermissionController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:permission-view', only: ['index', 'show']),
            new Middleware('permission:permission-create', only: ['store']),
            new Middleware('permission:permission-edit', only: ['update']),
            new Middleware('permission:permission-delete', only: ['destroy', 'forceDelete']),
            new Middleware('permission:permission-restore', only: ['restore']),
        ];
    }

    /**
     * List all permissions with pagination and optional search.
     */
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('permission-view'), 403);

        $sortMap = [
            'name' => 'name',
            'guard' => 'guard_name',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        $sortBy = $sortMap[$request->input('sort_by', 'name')] ?? 'name';
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->input('per_page', 15), 1), 500);
        $deletedFilter = $request->input('deleted');

        $permissions = Permission::query()
            ->when($deletedFilter === 'with', fn ($q) => $q->withTrashed())
            ->when($deletedFilter === 'only', fn ($q) => $q->onlyTrashed())
            ->with('roles')
            ->withCount('roles')
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where('name', 'like', '%' . $request->input('search') . '%'),
            )
            ->when(
                $request->filled('guard_name'),
                fn ($q) => $q->where('guard_name', $request->input('guard_name')),
            )
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);

        return response()->json($permissions);
    }

    /**
     * Create a new permission.
     */
    public function store(StorePermissionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        $permission = Permission::create([
            'name'       => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? 'web',
        ]);
        
        if (array_key_exists('roles', $validated)) {
            $permission->syncRoles($validated['roles']);
        }

        return response()->json([
            'message' => "Permission [{$permission->name}] created successfully.",
            'data'    => $permission,
        ], 201);
    }

    /**
     * Show a single permission with all roles that have it.
     */
    public function show(Request $request, int|string $permission): JsonResponse
    {
        abort_unless($request->user()?->can('permission-view'), 403);

        $permission = Permission::withTrashed()
            ->with('roles')
            ->withCount('roles')
            ->findOrFail($permission);

        return response()->json([
            'data' => $permission,
        ]);
    }

    /**
     * Update a permission's name.
     */
    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $validated = $request->validated();
        
        $permission->update([
            'name'       => $validated['name'],
            'guard_name' => $validated['guard_name'] ?? $permission->guard_name,
        ]);
        
        if (array_key_exists('roles', $validated)) {
            $permission->syncRoles($validated['roles']);
        }

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
    public function destroy(Request $request, Permission $permission): JsonResponse
    {
        abort_unless($request->user()?->can('permission-delete'), 403);

        $name = $permission->name;
        $permission->delete();

        return response()->json([
            'message' => "Permission [{$name}] temporarily deleted successfully.",
        ]);
    }

    public function restore(Request $request, int|string $permission): JsonResponse
    {
        abort_unless($request->user()?->can('permission-restore'), 403);

        $permission = Permission::withTrashed()
            ->with('roles')
            ->findOrFail($permission);

        if (! $permission->trashed()) {
            return response()->json([
                'message' => "Permission [{$permission->name}] is not deleted.",
                'data'    => $permission,
            ]);
        }

        $permission->restore();
        $permission->load('roles');

        return response()->json([
            'message' => "Permission [{$permission->name}] restored successfully.",
            'data'    => $permission,
        ]);
    }

    public function forceDelete(Request $request, int|string $permission): JsonResponse
    {
        abort_unless($request->user()?->can('permission-permanent-delete'), 403);

        $permission = Permission::withTrashed()->findOrFail($permission);

        $name = $permission->name;
        $permission->forceDelete();

        return response()->json([
            'message' => "Permission [{$name}] permanently deleted successfully.",
        ]);
    }
}
