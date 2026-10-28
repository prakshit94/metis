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
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:purchaseorder-view', only: ['index', 'downloadPdf', 'bulkDownloadPdf', 'getPayments']),
            new Middleware('permission:purchaseorder-create', only: ['store']),
            new Middleware('permission:purchaseorder-invoice-upload', only: ['uploadInvoice']),
            new Middleware('permission:purchaseorder-payment-create', only: ['addPayment']),
            new Middleware('permission:purchaseorder-approve', only: ['approve', 'reject']),
            new Middleware('permission:purchaseorder-delete', only: ['destroy']),
            new Middleware('permission:purchaseorder-restore', only: ['restore']),
        ];
    }

    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $query = PurchaseOrder::with(['supplier', 'warehouse', 'items', 'items.product', 'approver'])->latest();

            if ($lobState = $request->user()?->lob_state_name) {
                $query->whereHas('warehouse', function ($wq) use ($lobState) {
                    $wq->where('state', $lobState);
                });
            }
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
        $poQuery = PurchaseOrder::query();
        if ($lobState = $request->user()?->lob_state_name) {
            $poQuery->whereHas('warehouse', function ($wq) use ($lobState) {
                $wq->where('state', $lobState);
            });
        }

        $totals = $poQuery->toBase()->selectRaw("
            COUNT(*) as total,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) as pending,
            COALESCE(SUM(CASE WHEN status = 'received' THEN 1 ELSE 0 END), 0) as completed
        ")->first();
        $stats = [
            'total' => (int) $totals->total,
            'pending' => (int) $totals->pending,
            'completed' => (int) $totals->completed,
        ];

        $suppliers = Supplier::select('id', 'company_name', 'firstname', 'lastname')->get();
        $warehouses = Warehouse::select('id', 'name')
            ->where('status', 'active')
            ->when($request->user()?->lob_state_name, function ($query, $state) {
                $query->where('state', $state);
            })
            ->get();
        $products = Product::with('taxRate:id,rate')
            ->select('id', 'name', 'sku', 'image_path', 'supplier_id', 'purchase_price', 'tax_rate_id', 'default_discount')
            ->where('is_active', true)->get();
        $taxRates = TaxRate::select('id', 'name', 'rate')->where('is_active', true)->get();

        return view('procurement.purchase-orders.index', compact('stats', 'suppliers', 'warehouses', 'products', 'taxRates'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'supplier_id' => ['required', Rule::exists('suppliers', 'id')->whereNull('deleted_at')],
            'warehouse_id' => [
                'required',
                Rule::exists('warehouses', 'id')->whereNull('deleted_at')->where('status', 'active'),
                function ($attribute, $value, $fail) use ($request) {
                    if ($lobState = $request->user()?->lob_state_name) {
                        $wh = Warehouse::find($value);
                        if ($wh && $wh->state !== $lobState) {
                            $fail('Unauthorized warehouse selection.');
                        }
                    }
                },
            ],
            'expected_delivery_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => ['required', Rule::exists('products', 'id')->whereNull('deleted_at')->where('is_active', true)],
            'items.*.quantity' => 'required|numeric|min:0.01|decimal:0,2',
            'items.*.unit_price' => 'required|numeric|min:0|decimal:0,2',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.discount_amount' => 'nullable|numeric|min:0|decimal:0,2',
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

                $totalPrice = round((float) $item['quantity'] * (float) $item['unit_price'], 2); // gross
                $taxableAmount = max(0, $totalPrice - $discountAmount);
                $taxAmount = round(($taxableAmount * $taxRate) / 100, 2);
                $netAmount = round($taxableAmount + $taxAmount, 2);

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
        $this->authorizeWarehouse($order->warehouse_id);
        $order->load(['supplier', 'warehouse', 'items.product.taxRate']);

        $pdf = Pdf::loadView('procurement.purchase-orders.pdf', compact('order'));

        return $pdf->download($order->po_number.'.pdf');
    }

    public function bulkDownloadPdf(Request $request)
    {
        $ids = explode(',', $request->query('ids', ''));
        if (empty($ids) || empty($ids[0])) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'No purchase orders selected.'], 422);
            }

            return back()->with('error', 'No purchase orders selected.');
        }

        $orders = PurchaseOrder::with(['supplier', 'warehouse', 'items.product.taxRate'])
            ->whereIn('id', $ids)
            ->when(auth()->user()?->lob_state_name, fn ($q, $state) => $q->whereHas('warehouse', fn ($w) => $w->where('state', $state)))
            ->get();

        if ($orders->isEmpty()) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'No valid purchase orders found.'], 422);
            }

            return back()->with('error', 'No valid purchase orders found.');
        }

        $pdf = Pdf::loadView('procurement.purchase-orders.bulk-pdf', compact('orders'));

        return $pdf->download('Purchase_Orders_Bulk.pdf');
    }

    public function approve(PurchaseOrder $order): JsonResponse
    {
        $this->authorizeWarehouse($order->warehouse_id);

        return \DB::transaction(function () use ($order) {
            $order = PurchaseOrder::lockForUpdate()->findOrFail($order->id);
            if ($order->status !== 'pending') {
                return response()->json(['message' => 'Only pending orders can be approved.'], 400);
            }

            $order->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            return response()->json(['message' => 'Purchase Order approved successfully.', 'data' => $order]);
        });
    }

    public function reject(Request $request, PurchaseOrder $order): JsonResponse
    {
        $this->authorizeWarehouse($order->warehouse_id);

        return \DB::transaction(function () use ($order, $request) {
            $order = PurchaseOrder::lockForUpdate()->findOrFail($order->id);
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
        });
    }

    public function destroy(PurchaseOrder $order): JsonResponse
    {
        $this->authorizeWarehouse($order->warehouse_id);

        return \DB::transaction(function () use ($order) {
            $order = PurchaseOrder::lockForUpdate()->findOrFail($order->id);
            if (in_array($order->status, ['received', 'partially_received'])) {
                return response()->json(['message' => 'Cannot delete a Purchase Order that has already been processed.'], 400);
            }

            $order->delete();

            return response()->json(['message' => 'Purchase Order deleted successfully.']);
        });
    }

    public function restore($id): JsonResponse
    {
        $order = PurchaseOrder::withTrashed()->findOrFail($id);
        $this->authorizeWarehouse($order->warehouse_id);
        $order->restore();

        return response()->json(['message' => 'Purchase Order restored successfully.']);
    }

    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => 'required|string|in:approve,reject,delete,restore',
            'ids' => 'required|array|min:1',
            'ids.*' => 'required|integer|distinct|exists:purchase_orders,id',
            'rejection_reason' => 'required_if:action,reject|string|max:1000|nullable',
        ]);

        $permission = in_array($validated['action'], ['approve', 'reject'], true) ? 'purchaseorder-approve' : 'purchaseorder-'.$validated['action'];
        abort_unless($request->user()?->can($permission), 403);
        PurchaseOrder::withTrashed()->whereIn('id', $validated['ids'])->get()->each(fn ($order) => $this->authorizeWarehouse($order->warehouse_id));

        $action = $validated['action'];
        $ids = $validated['ids'];
        $reason = $validated['rejection_reason'] ?? null;

        return \DB::transaction(function () use ($action, $ids, $reason) {
            $orders = PurchaseOrder::withTrashed()->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $affected = 0;
            foreach ($orders as $order) {
                if ($action === 'restore') {
                    if ($order->trashed()) {
                        $order->restore();
                        $affected++;
                    }

                    continue;
                }
                if ($order->trashed()) {
                    continue;
                }
                if ($action === 'delete') {
                    if (! in_array($order->status, ['received', 'partially_received'], true)) {
                        $order->delete();
                        $affected++;
                    }
                } elseif ($order->status === 'pending') {
                    $order->update(['status' => $action === 'approve' ? 'approved' : 'rejected',
                        'approved_by' => auth()->id(), 'approved_at' => now(),
                        ...($action === 'reject' ? ['rejection_reason' => $reason] : [])]);
                    $affected++;
                }
            }
            $skipped = count($ids) - $affected;

            return response()->json(['message' => "{$affected} purchase order(s) updated; {$skipped} ineligible or unchanged order(s) skipped.",
                'affected' => $affected, 'skipped' => $skipped], $affected ? 200 : 422);
        });
    }

    public function uploadInvoice(Request $request, PurchaseOrder $order): JsonResponse
    {
        $this->authorizeWarehouse($order->warehouse_id);
        $request->validate([
            'invoice' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($request->hasFile('invoice')) {
            $path = $request->file('invoice')->store('invoices', 'public');
            if (! $path) {
                return response()->json(['message' => 'Could not store the invoice. Please try again.'], 500);
            }
            try {
                $oldPath = \DB::transaction(function () use ($order, $path) {
                    $locked = PurchaseOrder::lockForUpdate()->findOrFail($order->id);
                    $oldPath = $locked->invoice_path;
                    $locked->update(['invoice_path' => $path]);

                    return $oldPath;
                });
            } catch (\Throwable $e) {
                Storage::disk('public')->delete($path);
                throw $e;
            }
            if ($oldPath && $oldPath !== $path) {
                try {
                    Storage::disk('public')->delete($oldPath);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            return response()->json(['message' => 'Invoice uploaded successfully.', 'invoice_url' => $order->refresh()->invoice_url]);
        }

        return response()->json(['message' => 'No file was uploaded.'], 400);
    }

    public function getPayments(PurchaseOrder $order): JsonResponse
    {
        $this->authorizeWarehouse($order->warehouse_id);
        $payments = $order->payments()->with('recorder:id,name')->latest()->get();

        return response()->json(['data' => $payments]);
    }

    public function addPayment(Request $request, PurchaseOrder $order): JsonResponse
    {
        $this->authorizeWarehouse($order->warehouse_id);
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method' => 'required|string|max:255',
            'payment_reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        \DB::beginTransaction();
        try {
            $order = PurchaseOrder::lockForUpdate()->findOrFail($order->id);
            $payment = $order->payments()->create([
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
                'payment_method' => $validated['payment_method'],
                'payment_reference' => $validated['payment_reference'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'recorded_by' => auth()->id(),
            ]);

            $totalPaid = $order->payments()->sum('amount');
            $status = 'partial';
            if ($totalPaid >= $order->net_amount) {
                $status = 'paid';
            } elseif ($order->status === 'pending') {
                $status = 'advanced'; // Advanced payment before receipt
            }

            $order->update([
                'paid_amount' => $totalPaid,
                'payment_status' => $status,
            ]);

            \DB::commit();

            return response()->json(['message' => 'Payment recorded successfully', 'data' => $payment], 201);
        } catch (\Exception $e) {
            \DB::rollBack();

            return response()->json(['message' => 'Failed to record payment: '.$e->getMessage()], 500);
        }
    }

    private function authorizeWarehouse($warehouseId): void
    {
        if ($state = auth()->user()?->lob_state_name) {
            abort_unless(Warehouse::withTrashed()->whereKey($warehouseId)->where('state', $state)->exists(), 403);
        }
    }
}
