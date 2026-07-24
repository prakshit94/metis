<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Catalog\Models\ProductAttribute;
use App\Modules\Catalog\Models\ProductAttributeValue;
use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ProductAttributeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:productattribute-view', only: ['index', 'show']),
            new Middleware('permission:productattribute-create', only: ['store']),
            new Middleware('permission:productattribute-edit', only: ['update']),
            new Middleware('permission:productattribute-delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('product-view');

        $query = ProductAttribute::query()->with('values');

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if (in_array($sortBy, ['id', 'name', 'status', 'type', 'is_filterable'])) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = (int) $request->query('per_page', 10);
        $perPage = min(max($perPage, 1), 1000);

        $paginator = $query->paginate($perPage);

        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('product-create');

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:product_attributes,name',
            'type' => 'required|string|in:select,color,text',
            'status' => 'required|in:active,inactive',
            'is_filterable' => 'nullable|boolean',
        ]);

        $validated['is_filterable'] = $request->input('is_filterable', true);

        $model = ProductAttribute::create($validated);

        return response()->json([
            'message' => 'Product Attribute created successfully.',
            'data' => $model,
        ], 201);
    }

    public function show(ProductAttribute $attribute): JsonResponse
    {
        $this->authorize('product-view');
        $attribute->load('values');

        return response()->json(['data' => $attribute]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $this->authorize('product-edit');

        $model = ProductAttribute::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:product_attributes,name,'.$model->id,
            'type' => 'sometimes|required|string|in:select,color,text',
            'status' => 'sometimes|required|in:active,inactive',
            'is_filterable' => 'nullable|boolean',
        ]);

        $model->update($validated);

        return response()->json([
            'message' => 'Product Attribute updated successfully.',
            'data' => $model,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->authorize('product-delete');
        $model = ProductAttribute::findOrFail($id);
        $model->delete();

        return response()->json([
            'message' => 'Product Attribute deleted successfully.',
        ]);
    }

    // Value Management
    public function storeValue(Request $request, ProductAttribute $attribute): JsonResponse
    {
        $this->authorize('product-create');

        $data = $request->validate([
            'value' => 'required|string|max:255',
            'color_code' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);

        $value = $attribute->values()->create($data);

        return response()->json([
            'message' => 'Value added successfully.',
            'data' => $value,
        ], 201);
    }

    public function updateValue(Request $request, $id): JsonResponse
    {
        $this->authorize('product-edit');

        $value = ProductAttributeValue::findOrFail($id);

        $data = $request->validate([
            'value' => 'sometimes|required|string|max:255',
            'color_code' => 'nullable|string|max:255',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        $value->update($data);

        return response()->json([
            'message' => 'Value updated successfully.',
            'data' => $value,
        ]);
    }

    public function destroyValue($id): JsonResponse
    {
        $this->authorize('product-delete');
        $value = ProductAttributeValue::findOrFail($id);
        $value->delete();

        return response()->json([
            'message' => 'Value deleted successfully.',
        ]);
    }
}
