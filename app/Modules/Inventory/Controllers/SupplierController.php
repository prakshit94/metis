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
            new Middleware('permission:supplier-view', only: ['index']),
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

            $perPage = $request->query('per_page', 15);

            return response()->json($query->paginate($perPage));
        }

        $stats = [
            'total' => Supplier::count(),
            'active' => Supplier::where('status', 'active')->count(),
            'inactive' => Supplier::where('status', '!=', 'active')->count(),
        ];

        return view('procurement.suppliers.index', compact('stats'));
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

        $supplier = Supplier::create($validated);

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
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:suppliers,id',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];

        if ($action === 'delete') {
            Supplier::whereIn('id', $ids)->delete();

            return response()->json(['message' => 'Suppliers deleted successfully']);
        }

        Supplier::whereIn('id', $ids)->update(['status' => $action]);

        return response()->json(['message' => 'Suppliers status updated successfully']);
    }
}
