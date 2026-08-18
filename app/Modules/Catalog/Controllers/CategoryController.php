<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Catalog\Models\Category;
use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:category-view', only: ['index', 'show']),
            new Middleware('permission:category-create', only: ['store']),
            new Middleware('permission:category-edit', only: ['update']),
            new Middleware('permission:category-delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): JsonResponse
    {


        $query = Category::query()->with('parent')->withCount('products');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if (in_array($sortBy, ['id', 'name', 'status', 'parent_id', 'is_active'])) {
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
            'name' => 'required|string|max:255|unique:categories,name',
            'parent_id' => 'nullable',
            'status' => 'required|in:active,inactive',
            'is_active' => 'nullable',
            'image' => 'nullable|image|max:2048',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $validated['is_active'] = $request->has('is_active')
            ? filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN)
            : true;

        if ($request->has('parent_id') && ($request->input('parent_id') === '' || $request->input('parent_id') === 'null' || $request->input('parent_id') === null)) {
            $validated['parent_id'] = null;
        } elseif ($request->has('parent_id')) {
            $validated['parent_id'] = (int) $request->input('parent_id');
        }

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('categories', 'public');
        }

        $model = Category::create($validated);

        return response()->json([
            'message' => 'Category created successfully.',
            'data' => $model->load('parent')->loadCount('products'),
        ], 201);
    }

    public function show(Category $model): JsonResponse
    {


        return response()->json(['data' => $model->load('parent')->loadCount('products')]);
    }

    public function update(Request $request, $id): JsonResponse
    {


        $model = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:categories,name,'.$model->id,
            'parent_id' => 'nullable',
            'status' => 'sometimes|required|in:active,inactive',
            'is_active' => 'nullable',
            'image' => 'nullable|image|max:2048',
        ]);

        if (isset($validated['name'])) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        if ($request->has('is_active')) {
            $validated['is_active'] = filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($request->has('parent_id')) {
            if ($request->input('parent_id') === '' || $request->input('parent_id') === 'null' || $request->input('parent_id') === null) {
                $validated['parent_id'] = null;
            } else {
                $validated['parent_id'] = (int) $request->input('parent_id');
            }
        }

        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($model->image) {
                Storage::disk('public')->delete($model->image);
            }
            $validated['image'] = $request->file('image')->store('categories', 'public');
        } elseif ($request->input('clear_image') === '1') {
            if ($model->image) {
                Storage::disk('public')->delete($model->image);
            }
            $validated['image'] = null;
        }

        $model->update($validated);

        return response()->json([
            'message' => 'Category updated successfully.',
            'data' => $model->load('parent')->loadCount('products'),
        ]);
    }

    public function destroy($id): JsonResponse
    {

        $model = Category::findOrFail($id);

        // Delete image if exists
        if ($model->image) {
            Storage::disk('public')->delete($model->image);
        }

        $model->delete();

        return response()->json([
            'message' => 'Category deleted successfully.',
        ]);
    }
}
