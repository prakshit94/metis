<?php

declare(strict_types=1);

namespace App\Modules\Inventory\Controllers;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\TaxRate;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Inventory\Models\PurchaseOrder;
use App\Modules\Inventory\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;

class PurchaseOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:purchaseorder-view', only: ['index']),
            new Middleware('permission:purchaseorder-create', only: ['store']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = PurchaseOrder::with(['supplier', 'warehouse', 'items', 'items.product', 'approver'])->latest();

            if ($request->has('trashed')) {
                if ($request->query('trashed') === 'only') {
                    $query->onlyTrashed();
                } elseif ($request->query('trashed') === 'with') {
                    $query->withTrashed();
                }
            }

            if ($request->has('search') && ! empty($request->query('search'))) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('po_number', 'like', "%{$search}%")
                        ->orWhereHas('supplier', function ($sq) use ($search) {
                            $sq->where('firstname', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        });
                });
            }

            if ($request->has('status') && ! empty($request->query('status'))) {
                $query->where('status', $request->query('status'));
            }

            return response()->json($query->paginate(15));
        }

        // Stats for cards
        $stats = [
            'total' => PurchaseOrder::count(),
            'pending' => PurchaseOrder::where('status', 'pending')->count(),
            'completed' => PurchaseOrder::where('status', 'received')->count(),
        ];

        $suppliers = Supplier::select('id', 'company_name', 'firstname', 'lastname')->get();
        $warehouses = Warehouse::select('id', 'name')->where('is_active', true)->get();
        $products = Product::with('taxRate:id,rate')
            ->select('id', 'name', 'sku', 'image_path', 'supplier_id', 'purchase_price', 'tax_rate_id', 'default_discount')
            ->where('is_active', true)->get();
        $taxRates = TaxRate::select('id', 'name', 'rate')->where('is_active', true)->get();

        return view('procurement.purchase-orders.index', compact('stats', 'suppliers', 'warehouses', 'products', 'taxRates'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ]);

        \DB::beginTransaction();
        try {
            $poNumber = 'PO-'.date('Ymd').'-'.strtoupper(\Str::random(4));

            $po = PurchaseOrder::create([
                'po_number' => $poNumber,
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'pending',
                'created_by' => auth()->id(),
            ]);

            $totalAmount = 0;
            $poTaxAmount = 0;
            $poDiscountAmount = 0;
            $poNetAmount = 0;

            foreach ($validated['items'] as $item) {
                $taxRate = isset($item['tax_rate']) ? (float) $item['tax_rate'] : 0.0;
                $discountAmount = isset($item['discount_amount']) ? (float) $item['discount_amount'] : 0.0;

                $totalPrice = $item['quantity'] * $item['unit_price']; // gross
                $taxAmount = ($totalPrice * $taxRate) / 100;
                $netAmount = ($totalPrice + $taxAmount) - $discountAmount;

                $totalAmount += $totalPrice;
                $poTaxAmount += $taxAmount;
                $poDiscountAmount += $discountAmount;
                $poNetAmount += $netAmount;

                $po->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'total_price' => $totalPrice,
                    'net_amount' => $netAmount,
                ]);
            }

            $po->update([
                'total_amount' => $totalAmount,
                'tax_amount' => $poTaxAmount,
                'discount_amount' => $poDiscountAmount,
                'net_amount' => $poNetAmount,
            ]);

            \DB::commit();

            return response()->json(['message' => 'Purchase Order created successfully', 'data' => $po], 201);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json(['message' => 'Failed to create PO: '.$e->getMessage()], 500);
        }
    }

    public function downloadPdf(PurchaseOrder $order)
    {
        $order->load(['supplier', 'warehouse', 'items.product.taxRate']);

        $pdf = Pdf::loadView('procurement.purchase-orders.pdf', compact('order'));

        return $pdf->download($order->po_number.'.pdf');
    }

    public function bulkDownloadPdf(Request $request)
    {
        $ids = explode(',', $request->query('ids', ''));
        if (empty($ids) || empty($ids[0])) {
            return back()->with('error', 'No purchase orders selected.');
        }

        $orders = PurchaseOrder::with(['supplier', 'warehouse', 'items.product.taxRate'])
            ->whereIn('id', $ids)
            ->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'No valid purchase orders found.');
        }

        $pdf = Pdf::loadView('procurement.purchase-orders.bulk-pdf', compact('orders'));

        return $pdf->download('Purchase_Orders_Bulk.pdf');
    }

    public function approve(PurchaseOrder $order): JsonResponse
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Only pending orders can be approved.'], 400);
        }

        $order->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json(['message' => 'Purchase Order approved successfully.', 'data' => $order]);
    }

    public function reject(Request $request, PurchaseOrder $order): JsonResponse
    {
        if ($order->status !== 'pending') {
            return response()->json(['message' => 'Only pending orders can be rejected.'], 400);
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        $order->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return response()->json(['message' => 'Purchase Order rejected successfully.', 'data' => $order]);
    }

    public function destroy(PurchaseOrder $order): JsonResponse
    {
        if (in_array($order->status, ['received', 'partially_received'])) {
            return response()->json(['message' => 'Cannot delete a Purchase Order that has already been processed.'], 400);
        }

        $order->delete();

        return response()->json(['message' => 'Purchase Order deleted successfully.']);
    }

    public function restore($id): JsonResponse
    {
        $order = PurchaseOrder::withTrashed()->findOrFail($id);
        $order->restore();

        return response()->json(['message' => 'Purchase Order restored successfully.']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:approve,reject,delete,restore',
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'rejection_reason' => 'required_if:action,reject|string|max:1000|nullable',
        ]);

        $action = $validated['action'];
        $ids = $validated['ids'];
        $reason = $validated['rejection_reason'] ?? null;

        if ($action === 'delete') {
            $deletedCount = PurchaseOrder::whereIn('id', $ids)
                ->whereNotIn('status', ['received', 'partially_received'])
                ->delete();

            if ($deletedCount === 0) {
                return response()->json(['message' => 'Cannot delete processed Purchase Orders.'], 400);
            }

            return response()->json(['message' => 'Selected Purchase Orders deleted successfully.']);
        }

        if ($action === 'restore') {
            PurchaseOrder::withTrashed()->whereIn('id', $ids)->restore();

            return response()->json(['message' => 'Selected Purchase Orders restored successfully.']);
        }

        // Only apply to pending POs to prevent re-approving/rejecting already processed ones
        $query = PurchaseOrder::whereIn('id', $ids)->where('status', 'pending');

        if ($action === 'approve') {
            $query->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return response()->json(['message' => 'Selected Purchase Orders approved successfully.']);
        }

        if ($action === 'reject') {
            $query->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return response()->json(['message' => 'Selected Purchase Orders rejected successfully.']);
        }

        return response()->json(['message' => 'Invalid action.'], 400);
    }

    public function uploadInvoice(Request $request, PurchaseOrder $order): JsonResponse
    {
        $request->validate([
            'invoice' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('invoice')) {
            // Delete old invoice if it exists
            if ($order->invoice_path) {
                Storage::disk('public')->delete($order->invoice_path);
            }

            $path = $request->file('invoice')->store('invoices', 'public');

            $order->update([
                'invoice_path' => $path,
            ]);

            return response()->json([
                'message' => 'Invoice uploaded successfully.',
                'invoice_url' => $order->invoice_url,
            ]);
        }

        return response()->json(['message' => 'No file was uploaded.'], 400);
    }
}
