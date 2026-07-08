<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('product-view');
        
        $query = Warehouse::query();
        
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
        $this->authorize('product-create');
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'required|in:active,inactive',
        ]);
        
        // Handling specific fields for different models
        if ('Warehouse' === 'Brand' || 'Warehouse' === 'Category' || 'Warehouse' === 'UnitOfMeasure') {
            $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
        }
        
        if ('Warehouse' === 'HsnCode') {
            $validated = $request->validate([
                'code' => 'required|string|max:255',
                'description' => 'required|string|max:255',
            ]);
        }
        
        if ('Warehouse' === 'TaxRate') {
            $validated['rate'] = $request->input('rate', 0);
        }
        
        if ('Warehouse' === 'UnitOfMeasure') {
            $validated['short_name'] = $request->input('short_name', substr($validated['name'], 0, 3));
        }

        if ('Warehouse' === 'Warehouse') {
            $validated['code'] = $request->input('code', strtoupper(substr($validated['name'], 0, 3)));
        }

        $model = Warehouse::create($validated);
        
        return response()->json([
            'message' => 'Warehouse created successfully.',
            'data' => $model
        ], 201);
    }

    public function show(Warehouse $model): JsonResponse
    {
        $this->authorize('product-view');
        return response()->json(['data' => $model]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $this->authorize('product-edit');
        
        $model = Warehouse::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|required|in:active,inactive',
        ]);
        
        if ('Warehouse' === 'Brand' || 'Warehouse' === 'Category' || 'Warehouse' === 'UnitOfMeasure') {
            if (isset($validated['name'])) {
                $validated['slug'] = \Illuminate\Support\Str::slug($validated['name']);
            }
        }
        
        if ('Warehouse' === 'HsnCode') {
            $validated = $request->validate([
                'code' => 'sometimes|required|string|max:255',
                'description' => 'sometimes|required|string|max:255',
            ]);
        }

        if ('Warehouse' === 'TaxRate') {
            $validated['rate'] = $request->input('rate', $model->rate);
        }
        
        if ('Warehouse' === 'UnitOfMeasure') {
            $validated['short_name'] = $request->input('short_name', $model->short_name);
        }

        if ('Warehouse' === 'Warehouse') {
            $validated['code'] = $request->input('code', $model->code);
        }
        
        $model->update($validated);
        
        return response()->json([
            'message' => 'Warehouse updated successfully.',
            'data' => $model
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->authorize('product-delete');
        $model = Warehouse::findOrFail($id);
        $model->delete();
        
        return response()->json([
            'message' => 'Warehouse deleted successfully.'
        ]);
    }
}
