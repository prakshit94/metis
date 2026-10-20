<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class SupplierController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:supplier-view', only: ['index', 'show']),
            new Middleware('permission:supplier-create', only: ['store']),
            new Middleware('permission:supplier-edit', only: ['update']),
            new Middleware('permission:supplier-delete', only: ['destroy']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = Supplier::with('products:id,supplier_id,name,sku')->latest();

            if ($request->has('search') && ! empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('company_name', 'like', "%{$search}%")
                        ->orWhere('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('gst_no', 'like', "%{$search}%");
                });
            }

            if ($request->has('status') && ! empty($request->query('status'))) {
                $query->where('status', $request->query('status'));
            }

            $sort = $request->query('sort_by', 'company_name');
            $direction = $request->query('sort_dir', 'asc');
            if (in_array($sort, ['company_name', 'firstname', 'lastname', 'email', 'phone', 'status', 'credit_limit', 'credit_days', 'created_at', 'id'], true) && in_array($direction, ['asc', 'desc'], true)) {
                $query->reorder($sort, $direction)->orderBy('id');
            }
            $perPage = min(100, max(1, (int) $request->query('per_page', 15)));

            return response()->json($query->paginate($perPage));
        }

        $totals = Supplier::query()->toBase()->selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) as active,
            COALESCE(SUM(CASE WHEN status != 'active' THEN 1 ELSE 0 END), 0) as inactive
        ")->first();
        $stats = [
            'total' => (int) $totals->total,
            'active' => (int) $totals->active,
            'inactive' => (int) $totals->inactive,
        ];

        return view('procurement.suppliers.index', compact('stats'));
    }

    public function show(Supplier $supplier): JsonResponse
    {
        return response()->json(['data' => $supplier->load('products:id,supplier_id,name,sku')]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:suppliers,email',
            'phone' => 'required|string|max:20|unique:suppliers,phone',
            'gst_no' => 'nullable|string|max:50',
            'pan_no' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'internal_notes' => 'nullable|string',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'village_id' => 'nullable|integer',
            'village_name' => 'nullable|string|max:255',
            'post_office' => 'nullable|string|max:255',
            'taluka' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',

        ]);

        $validated['credit_limit'] = $validated['credit_limit'] ?? 0;
        $validated['credit_days'] = $validated['credit_days'] ?? 0;

        $supplier = \Illuminate\Support\Facades\DB::transaction(fn () => Supplier::create($validated), 3);

        return response()->json([
            'message' => 'Supplier created successfully',
            'data' => $supplier,
        ], 201);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {

        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'firstname' => 'nullable|string|max:255',
            'lastname' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255|unique:suppliers,email,'.$supplier->id,
            'phone' => 'required|string|max:20|unique:suppliers,phone,'.$supplier->id,
            'gst_no' => 'nullable|string|max:50',
            'pan_no' => 'nullable|string|max:50',
            'credit_limit' => 'nullable|numeric|min:0',
            'credit_days' => 'nullable|integer|min:0',
            'status' => 'required|in:active,inactive',
            'internal_notes' => 'nullable|string',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'village_id' => 'nullable|integer',
            'village_name' => 'nullable|string|max:255',
            'post_office' => 'nullable|string|max:255',
            'taluka' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
        ]);

        foreach (['credit_limit', 'credit_days'] as $field) {
            if (array_key_exists($field, $validated)) { $validated[$field] = $validated[$field] ?? 0; }
        }

        $supplier->update($validated);

        return response()->json([
            'message' => 'Supplier updated successfully',
            'data' => $supplier,
        ]);
    }

    public function destroy(Supplier $supplier): JsonResponse
    {

        $supplier->delete();

        return response()->json(['message' => 'Supplier deleted successfully']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:active,inactive,delete',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|distinct|exists:suppliers,id',
        ]);

        abort_unless($request->user()?->can($validated['action'] === 'delete' ? 'supplier-delete' : 'supplier-edit'), 403);
        $action = $validated['action'];
        $ids = $validated['ids'];

        return \Illuminate\Support\Facades\DB::transaction(function () use ($action, $ids) {
            $suppliers = Supplier::whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            foreach ($suppliers as $supplier) {
                if ($action === 'delete') { $supplier->delete(); }
                else { $supplier->update(['status' => $action]); }
            }
            return response()->json(['message' => count($suppliers).' supplier(s) updated successfully.', 'affected' => count($suppliers)]);
        });
    }
}
