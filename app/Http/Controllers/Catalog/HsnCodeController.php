<?php

declare(strict_types=1);

namespace App\Http\Controllers\Catalog;

use App\Http\Controllers\Controller;
use App\Models\HsnCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HsnCodeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('product-view');

        $query = HsnCode::query();

        // HSN table has 'code' and 'description' — no 'name' column
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $sortBy  = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if (in_array($sortBy, ['id', 'code', 'description', 'status'], true)) {
            $query->orderBy($sortBy, $sortDir);
        }

        // Allow up to 1000 rows for client-side processing
        $perPage = (int) $request->query('per_page', 10);
        $perPage = min(max($perPage, 1), 1000);

        $paginator = $query->paginate($perPage);

        return response()->json($paginator);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('product-create');

        $validated = $request->validate([
            'code'        => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'rate'        => 'nullable|numeric|min:0|max:100',
            'status'      => 'required|in:active,inactive',
        ]);

        $model = HsnCode::create($validated);

        return response()->json([
            'message' => 'HSN code created successfully.',
            'data'    => $model,
        ], 201);
    }

    public function show(HsnCode $hsnCode): JsonResponse
    {
        $this->authorize('product-view');
        return response()->json(['data' => $hsnCode]);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $this->authorize('product-edit');

        $model = HsnCode::findOrFail($id);

        $validated = $request->validate([
            'code'        => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'rate'        => 'sometimes|nullable|numeric|min:0|max:100',
            'status'      => 'sometimes|required|in:active,inactive',
        ]);

        $model->update($validated);

        return response()->json([
            'message' => 'HSN code updated successfully.',
            'data'    => $model,
        ]);
    }

    public function destroy($id): JsonResponse
    {
        $this->authorize('product-delete');
        $model = HsnCode::findOrFail($id);
        $model->delete();

        return response()->json(['message' => 'HSN code deleted successfully.']);
    }
}
