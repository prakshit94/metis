<?php

declare(strict_types=1);

namespace App\Modules\Catalog\Controllers;

use App\Modules\Catalog\Models\TaxRate;
use App\Modules\Core\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TaxRateController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:taxrate-view', only: ['index', 'show']),
            new Middleware('permission:taxrate-create', only: ['store']),
            new Middleware('permission:taxrate-edit', only: ['update']),
            new Middleware('permission:taxrate-delete', only: ['destroy']),
        ];
    }

    public function index(Request $request): JsonResponse
    {


        $query = TaxRate::query();

        if ($search = $request->query('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');

        if (in_array($sortBy, ['id', 'name', 'rate', 'status'], true)) {
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


        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $validated['rate'] = $request->input('rate', 0);

        $model = TaxRate::create($validated);

        return response()->json([
            'message' => 'Tax rate created successfully.',
            'data' => $model,
        ], 201);
    }

    public function show(TaxRate $taxRate): JsonResponse
    {


        return response()->json(['data' => $taxRate]);
    }

    public function update(Request $request, $id): JsonResponse
    {


        $model = TaxRate::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'rate' => 'sometimes|nullable|numeric|min:0|max:100',
            'status' => 'sometimes|required|in:active,inactive',
        ]);

        if ($request->has('rate')) {
            $validated['rate'] = $request->input('rate', $model->rate);
        }

        $model->update($validated);

        return response()->json([
            'message' => 'Tax rate updated successfully.',
            'data' => $model,
        ]);
    }

    public function destroy($id): JsonResponse
    {

        $model = TaxRate::findOrFail($id);
        $model->delete();

        return response()->json(['message' => 'Tax rate deleted successfully.']);
    }
}
