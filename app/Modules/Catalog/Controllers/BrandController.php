<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Catalog\Models\Brand;
use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Str;

class BrandController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:brand-view', only: ['index', 'show']),
            new Middleware('permission:brand-create', only: ['store']),
            new Middleware('permission:brand-edit', only: ['update']),
            new Middleware('permission:brand-delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): JsonResponse
    {

        $query = Brand::query();

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if (in_array($sortBy, ['id', 'name', 'status'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = (int) $request->query('per_page', 10);
        $perPage = min(max($perPage, 1), 1000);

        $paginator = $query->paginate($perPage);

        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $model = Brand::create($validated);

        return response()->json([
            'message' => 'Brand created successfully.',
            'data' => $model,
        ], 201);
    }

    public function show(Brand $model): JsonResponse
    {

        return response()->json(['data' => $model]);
    }

    public function update(Request $request, $id): JsonResponse
    {

        $model = Brand::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $model->update($validated);

        return response()->json([
            'message' => 'Brand updated successfully.',
            'data' => $model,
        ]);
    }

    public function destroy($id): JsonResponse
    {

        $model = Brand::findOrFail($id);
        $model->delete();

        return response()->json([
            'message' => 'Brand deleted successfully.',
        ]);
    }
}
