<?php

namespace App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Party;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['index', 'show', 'bulkExport']),
            new Middleware('permission:orders.create', only: ['create', 'store', 'bulkImport', 'bulkImportTemplate']),
            new Middleware('permission:orders.edit', only: ['edit']),
            new Middleware('permission:orders.delete', only: ['destroy']),
            new Middleware('permission:orders.confirm', only: ['confirm']),
            new Middleware('permission:orders.ship', only: ['ship']),
            new Middleware('permission:orders.dispatch', only: ['dispatch']),
            new Middleware('permission:orders.processing', only: ['markProcessing']),
            new Middleware('permission:orders.deliver', only: ['markDelivered']),
            new Middleware('permission:orders.cancel', only: ['cancel']),
            new Middleware('permission:orders.return', only: ['markReturned']),
            new Middleware('permission:orders.invoice_pdf', only: ['downloadInvoice']),
            new Middleware('permission:orders.generate_invoice', only: ['generateInvoice']),
            new Middleware('permission:orders.cod', only: ['downloadReceipt']),
            new Middleware('permission:orders.receipt', only: ['receipt']),
            new Middleware('permission:orders.bulk_status', only: ['bulkStatus', 'bulkStoreVerification']),
            new Middleware('permission:orders.bulk_print', only: ['bulkPrint']),
            new Middleware('permission:orders.revert_status', only: ['revertStatus']),
        ];
    }

    public function index(Request $request)
    {
        $query = Order::with([
            'party',
            'warehouse',
            'invoice.payments',
            'items.product',
            'shipments.events',
            'payments',
            'creator',
            'updater',
        ])->withCount('items');

        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('view_all_order')) {
            $query->where('created_by', $user->id);
        }
        if ($user && $user->can('view_all_order') && !$user->hasAnyRole(['Super Admin', 'Admin'])) {
            $query->where('status', '!=', 'pending');
        }
        $this->applyOrderActionPermissionScope($query, $user);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where('order_no', 'LIKE', "%{$s}%")
                    ->orWhereHas('party', function ($q) use ($s) {
                        $q->where('firstname', 'LIKE', "%{$s}%")
                            ->orWhere('lastname', 'LIKE', "%{$s}%")
                            ->orWhere('company_name', 'LIKE', "%{$s}%")
                            ->orWhere('phone', 'LIKE', "%{$s}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $requestedStatuses = array_filter(array_map('trim', explode(',', $request->status)));
            $hasFutureOrder = in_array('future_order', $requestedStatuses, true);
            $hasPending     = in_array('pending', $requestedStatuses, true);

            $realStatuses = array_values(array_filter($requestedStatuses, fn($s) => !in_array($s, ['future_order', 'pending'])));
            if (in_array('dispatched', $realStatuses, true)) {
                $realStatuses[] = 'shipped';
                $realStatuses   = array_values(array_unique($realStatuses));
            }

            $query->where(function ($q) use ($hasFutureOrder, $hasPending, $realStatuses) {
                $first = true;

                if ($hasFutureOrder) {
                    $q->where(function ($sub) {
                        $sub->where('status', 'pending')->where('is_draft', true);
                    });
                    $first = false;
                }

                if ($hasPending) {
                    $method = $first ? 'where' : 'orWhere';
                    $q->$method(function ($sub) {
                        $sub->where('status', 'pending')
                            ->where(function ($s2) {
                                $s2->where('is_draft', false)->orWhereNull('is_draft');
                            });
                    });
                    $first = false;
                }

                if (!empty($realStatuses)) {
                    $method = $first ? 'whereIn' : 'orWhereIn';
                    $q->$method('status', $realStatuses);
                }
            });
        }

        if ($request->filled('product')) {
            $productIds = array_filter(array_map('intval', explode(',', $request->product)));
            if (!empty($productIds)) {
                $query->whereHas('items', function ($q) use ($productIds) {
                    $q->whereIn('product_id', $productIds);
                });
            }
        }

        if ($request->filled('fulfillment')) {
            if ($request->fulfillment === 'unfulfillable') {
                $query->where('status', 'pending')
                      ->whereHas('items', function ($q) {
                          $q->whereRaw('quantity > (IFNULL((SELECT SUM(quantity - reserved_qty) FROM stocks WHERE stocks.product_id = order_items.product_id AND stocks.warehouse_id = orders.warehouse_id AND stocks.deleted_at IS NULL), 0))');
                      });
            } elseif ($request->fulfillment === 'fulfillable') {
                $query->where(function ($query) {
                    $query->whereIn('status', ['confirmed', 'processing'])
                          ->orWhere(function ($q) {
                              $q->where('status', 'pending')
                                ->whereDoesntHave('items', function ($iq) {
                                    $iq->whereRaw('quantity > (IFNULL((SELECT SUM(quantity - reserved_qty) FROM stocks WHERE stocks.product_id = order_items.product_id AND stocks.warehouse_id = orders.warehouse_id AND stocks.deleted_at IS NULL), 0))');
                                });
                          });
                });
            }
        }

        if ($request->filled('state') || $request->filled('district') || $request->filled('taluka') || $request->filled('village')) {
            $query->whereHas('shippingAddress.village', function ($q) use ($request) {
                if ($request->filled('state')) {
                    $q->whereIn('state_name', array_map('trim', explode(',', $request->state)));
                }
                if ($request->filled('district')) {
                    $q->whereIn('district_name', array_map('trim', explode(',', $request->district)));
                }
                if ($request->filled('taluka')) {
                    $q->whereIn('taluka_name', array_map('trim', explode(',', $request->taluka)));
                }
                if ($request->filled('village')) {
                    $q->whereIn('village_name', array_map('trim', explode(',', $request->village)));
                }
            });
        }

        if ($request->filled('carrier')) {
            $carriers = array_filter(array_map('trim', explode(',', $request->carrier)));
            if (!empty($carriers)) {
                $query->whereHas('shipments', function ($q) use ($carriers) {
                    $q->whereIn('carrier_name', $carriers);
                });
            }
        }

        if ($request->filled('from_date')) {
            $query->whereDate('order_date', '>=', $request->from_date);
        }
        if ($request->filled('to_date')) {
            $query->whereDate('order_date', '<=', $request->to_date);
        }

        // Stats Query
        $statsQuery = clone $query;
        $counts = $statsQuery->select([
            DB::raw("COUNT(*) as total"),
            DB::raw("SUM(orders.net_amount) as total_amount"),
            DB::raw("SUM(CASE WHEN orders.status = 'pending' AND orders.is_draft = 1 THEN 1 ELSE 0 END) as future_order"),
            DB::raw("SUM(CASE WHEN orders.status = 'pending' AND orders.is_draft = 1 THEN orders.net_amount ELSE 0 END) as future_order_amount"),
            DB::raw("SUM(CASE WHEN orders.status = 'pending' AND (orders.is_draft = 0 OR orders.is_draft IS NULL) THEN 1 ELSE 0 END) as pending"),
            DB::raw("SUM(CASE WHEN orders.status = 'pending' AND (orders.is_draft = 0 OR orders.is_draft IS NULL) THEN orders.net_amount ELSE 0 END) as pending_amount"),
            DB::raw("SUM(CASE WHEN orders.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed"),
            DB::raw("SUM(CASE WHEN orders.status = 'confirmed' THEN orders.net_amount ELSE 0 END) as confirmed_amount"),
            DB::raw("SUM(CASE WHEN orders.status = 'processing' THEN 1 ELSE 0 END) as processing"),
            DB::raw("SUM(CASE WHEN orders.status = 'processing' THEN orders.net_amount ELSE 0 END) as processing_amount"),
            DB::raw("SUM(CASE WHEN orders.status = 'ready_to_ship' THEN 1 ELSE 0 END) as ready_to_ship"),
            DB::raw("SUM(CASE WHEN orders.status = 'ready_to_ship' THEN orders.net_amount ELSE 0 END) as ready_to_ship_amount"),
            DB::raw("SUM(CASE WHEN orders.status IN ('dispatched', 'shipped') THEN 1 ELSE 0 END) as dispatched"),
            DB::raw("SUM(CASE WHEN orders.status IN ('dispatched', 'shipped') THEN orders.net_amount ELSE 0 END) as dispatched_amount"),
            DB::raw("SUM(CASE WHEN orders.status = 'delivered' THEN 1 ELSE 0 END) as delivered"),
            DB::raw("SUM(CASE WHEN orders.status = 'delivered' THEN orders.net_amount ELSE 0 END) as delivered_amount"),
            DB::raw("SUM(CASE WHEN orders.status = 'returned' THEN 1 ELSE 0 END) as returned"),
            DB::raw("SUM(CASE WHEN orders.status = 'returned' THEN orders.net_amount ELSE 0 END) as returned_amount"),
            DB::raw("SUM(CASE WHEN orders.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
            DB::raw("SUM(CASE WHEN orders.status = 'cancelled' THEN orders.net_amount ELSE 0 END) as cancelled_amount")
        ])->toBase()->first();

        $stats = [
            'total'                 => (int) ($counts->total ?? 0),
            'total_amount'          => (float) ($counts->total_amount ?? 0),
            'future_order'          => (int) ($counts->future_order ?? 0),
            'future_order_amount'   => (float) ($counts->future_order_amount ?? 0),
            'pending'               => (int) ($counts->pending ?? 0),
            'pending_amount'        => (float) ($counts->pending_amount ?? 0),
            'confirmed'             => (int) ($counts->confirmed ?? 0),
            'confirmed_amount'      => (float) ($counts->confirmed_amount ?? 0),
            'processing'            => (int) ($counts->processing ?? 0),
            'processing_amount'     => (float) ($counts->processing_amount ?? 0),
            'ready_to_ship'         => (int) ($counts->ready_to_ship ?? 0),
            'ready_to_ship_amount'  => (float) ($counts->ready_to_ship_amount ?? 0),
            'dispatched'            => (int) ($counts->dispatched ?? 0),
            'dispatched_amount'     => (float) ($counts->dispatched_amount ?? 0),
            'delivered'             => (int) ($counts->delivered ?? 0),
            'delivered_amount'      => (float) ($counts->delivered_amount ?? 0),
            'returned'              => (int) ($counts->returned ?? 0),
            'returned_amount'       => (float) ($counts->returned_amount ?? 0),
            'cancelled'             => (int) ($counts->cancelled ?? 0),
            'cancelled_amount'      => (float) ($counts->cancelled_amount ?? 0),
        ];

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        if (in_array($sortField, ['id', 'order_no', 'net_amount', 'order_date', 'status'])) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest('id');
        }
        $perPage = $request->integer('limit', 10);
        $orders = $query->paginate($perPage);

        $statusesList = $this->allowedOrderFilterStatuses($user);
        $productsList = Product::where('status', '!=', 'draft')->orderBy('name')->get(['id', 'name', 'sku']);

        $statesList = \Illuminate\Support\Facades\Cache::remember('geo_states', 3600, function () {
            return \App\Models\Village::distinct()->pluck('state_name')->filter()->sort()->values();
        });

        $districtsList = \Illuminate\Support\Facades\Cache::remember('geo_districts_' . $request->state, 3600, function () use ($request) {
            return \App\Models\Village::when($request->filled('state'), function ($q) use ($request) {
                $q->whereIn('state_name', array_map('trim', explode(',', $request->state)));
            })->distinct()->pluck('district_name')->filter()->sort()->values();
        });

        $talukasList = \Illuminate\Support\Facades\Cache::remember('geo_talukas_' . md5($request->state . '_' . $request->district), 3600, function () use ($request) {
            return \App\Models\Village::when($request->filled('state'), function ($q) use ($request) {
                $q->whereIn('state_name', array_map('trim', explode(',', $request->state)));
            })->when($request->filled('district'), function ($q) use ($request) {
                $q->whereIn('district_name', array_map('trim', explode(',', $request->district)));
            })->distinct()->pluck('taluka_name')->filter()->sort()->values();
        });

        $villagesList = \Illuminate\Support\Facades\Cache::remember('geo_villages_' . md5($request->state . '_' . $request->district . '_' . $request->taluka), 3600, function () use ($request) {
            if (!$request->filled('taluka')) {
                return collect([]);
            }
            return \App\Models\Village::when($request->filled('state'), function ($q) use ($request) {
                $q->whereIn('state_name', array_map('trim', explode(',', $request->state)));
            })->when($request->filled('district'), function ($q) use ($request) {
                $q->whereIn('district_name', array_map('trim', explode(',', $request->district)));
            })->when($request->filled('taluka'), function ($q) use ($request) {
                $q->whereIn('taluka_name', array_map('trim', explode(',', $request->taluka)));
            })->distinct()->pluck('village_name')->filter()->sort()->values();
        });

        $services = \App\Models\Service::active()->get();
        $carriersList = $services->pluck('name')
            ->merge(['BlueDart', 'Delhivery', 'DTDC', 'Ecom Express', 'FedEx', 'India Post', 'Shadowfax', 'XpressBees', 'DHL'])
            ->filter()
            ->unique()
            ->sort()
            ->values();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'orders' => $orders,
                'stats' => $stats,
                'allowed_filter_statuses' => $statusesList,
                'districts' => $districtsList,
                'talukas' => $talukasList,
                'villages' => $villagesList,
                'carriers' => $carriersList,
            ]);
        }

        return view('orders', compact(
            'orders',
            'stats',
            'statusesList',
            'productsList',
            'statesList',
            'districtsList',
            'talukasList',
            'villagesList',
            'services',
            'carriersList'
        ));
    }

    public function create()
    {
        $warehouses   = Warehouse::orderBy('name')->get();
        $parties      = Party::orderBy('firstname')->get();
        $activeOffers = \App\Models\Offer::with('product')->active()->orderByDesc('priority')->orderBy('id')->get();
        $activeCoupons = \App\Models\Coupon::where('is_active', true)->get();
        $categories   = \App\Models\Category::whereNull('parent_id')->with('children')->orderBy('name')->get();
        $initialCustomer = null;

        if (request()->filled('customer_id')) {
            $initialCustomer = Party::with([
                'addresses.village.services',
                'orders' => function ($q) {
                    $q->latest()->limit(10)->with([
                        'items.product:id,name,sku,image_path',
                        'warehouse:id,name',
                        'shippingAddress:id,party_id,label,address_line_1,address_line_2,city,state,pincode',
                        'billingAddress:id,party_id,label,address_line_1,address_line_2,city,state,pincode',
                        'appliedOffer:id,name,discount_type,value',
                    ]);
                },
            ])->find(request()->integer('customer_id'));
        }

        $hideSidebar = true;
        $lockSearch = true;

        return view('orders.create', compact('warehouses', 'parties', 'activeOffers', 'activeCoupons', 'categories', 'hideSidebar', 'lockSearch', 'initialCustomer'));
    }

    public function store(Request $request, OrderService $orderService)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:sale,purchase',
            'party_id' => 'required|exists:parties,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'shipping_address_id' => 'required|exists:party_addresses,id',
            'billing_address_id' => 'required|exists:party_addresses,id',
            'order_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variant_id' => 'nullable|integer',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.total_amount' => 'nullable|numeric|min:0',
            'is_draft' => 'nullable|boolean',
            'future_order_date' => 'nullable|date',
            'coupon_code' => 'nullable|string',
            'applied_offer_id' => 'nullable|integer|exists:offers,id',
            'applied_bogo_ids' => 'nullable|array',
            'applied_bogo_ids.*' => 'integer|exists:offers,id',
            'total_amount' => 'required|numeric',
            'tax_amount' => 'required|numeric',
            'discount_amount' => 'required|numeric',
            'net_amount' => 'required|numeric',
        ]);

        $order = $orderService->createOrder($validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Order created successfully.',
                'order' => $order,
            ]);
        }

        return redirect()->route('orders')->with('success', 'Order created successfully.');
    }

    public function edit(Order $order)
    {
        $warehouses = Warehouse::orderBy('name')->get();
        $parties = Party::orderBy('firstname')->get();
        $products = Product::orderBy('name')->get();
        return view('orders.edit', compact('order', 'warehouses', 'parties', 'products'));
    }

    public function show(string $id)
    {
        $order = Order::with([
            'party',
            'warehouse',
            'invoice.payments',
            'items.product',
            'shipments.events',
            'payments',
            'creator',
            'updater',
            'shippingAddress.village.services',
            'billingAddress.village.services',
        ])->findOrFail($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'order' => $order,
            ]);
        }

        return redirect()->route('orders');
    }

    public function confirm(string $id, InventoryService $inventoryService)
    {
        $order = Order::findOrFail($id);
        if ($order->status !== 'pending') {
            return response()->json(['error' => 'Only pending orders can be confirmed.'], 400);
        }

        try {
            $inventoryService->confirmOrder($order);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => 'Order confirmed and stock reserved.']);
    }

    public function ship(string $id, Request $request, InventoryService $inventoryService)
    {
        $order = Order::findOrFail($id);
        if ($order->status !== 'processing') {
            return response()->json(['error' => 'Only processing orders can be marked as ready to ship.'], 400);
        }

        $validated = $request->validate([
            'carrier_name' => 'required|string|max:255',
            'tracking_no' => 'required|string|max:255',
        ]);

        try {
            $inventoryService->readyToShipOrder($order, $validated['carrier_name'], $validated['tracking_no']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => 'Order marked as ready to ship.']);
    }

    public function dispatch(string $id, InventoryService $inventoryService)
    {
        $order = Order::findOrFail($id);
        if ($order->status !== 'ready_to_ship') {
            return response()->json(['error' => 'Only orders in ready to ship status can be dispatched.'], 400);
        }

        $shipment = $order->shipments()->first();
        if (!$shipment || !$shipment->carrier_name || !$shipment->tracking_no) {
            return response()->json(['error' => 'Order cannot be dispatched without valid carrier and tracking details.'], 400);
        }

        try {
            $inventoryService->dispatchOrder($order);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => 'Order dispatched and inventory updated.']);
    }

    public function markProcessing(string $id, OrderService $orderService)
    {
        $order = Order::findOrFail($id);
        if ($order->status !== 'confirmed') {
            return response()->json(['error' => 'Only confirmed orders can be moved to processing.'], 400);
        }

        try {
            $orderService->updateStatus($order, 'processing');
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => 'Order moved to processing.']);
    }

    public function markDelivered(string $id, InventoryService $inventoryService)
    {
        $order = Order::findOrFail($id);
        if (!in_array($order->status, ['dispatched', 'shipped'], true)) {
            return response()->json(['error' => 'Only dispatched orders can be marked as delivered.'], 400);
        }

        try {
            $inventoryService->deliverOrder($order);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => 'Order marked as delivered.']);
    }

    public function cancel(string $id, InventoryService $inventoryService)
    {
        $order = Order::findOrFail($id);
        if (in_array($order->status, ['delivered', 'cancelled', 'returned'], true)) {
            return response()->json(['error' => 'Delivered, cancelled, or returned orders cannot be cancelled.'], 400);
        }

        try {
            $inventoryService->cancelOrder($order);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => 'Order cancelled and stock released.']);
    }

    public function markReturned(string $id, InventoryService $inventoryService)
    {
        $order = Order::findOrFail($id);
        if (!in_array($order->status, ['delivered', 'dispatched', 'shipped'], true)) {
            return response()->json(['error' => 'Only delivered or dispatched orders can be marked as returned.'], 400);
        }

        try {
            $inventoryService->returnOrder($order);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => 'Order marked as returned and inventory restocked.']);
    }

    public function receipt(string $id, OrderService $orderService)
    {
        $order = $orderService->getOrderForReceipt((int) $id);
        return view('orders.receipt', compact('order'));
    }

    public function bulkStatus(Request $request, InventoryService $inventoryService, OrderService $orderService)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
            'status' => 'required|string|in:pending,confirmed,processing,ready_to_ship,dispatched,delivered,cancelled,returned',
            'carrier_name' => 'nullable|string|max:255',
            'tracking_no' => 'nullable|string|max:255',
        ]);

        $ids = $validated['order_ids'];
        $targetStatus = $validated['status'];
        $count = 0;
        $skipped = 0;

        DB::transaction(function () use ($ids, $targetStatus, $validated, $inventoryService, $orderService, &$count, &$skipped) {
            $orders = Order::whereIn('id', $ids)->lockForUpdate()->get();

            foreach ($orders as $order) {
                try {
                    if ($targetStatus === 'confirmed') {
                        if ($order->status === 'pending') {
                            $inventoryService->confirmOrder($order);
                            $count++;
                        } else {
                            $skipped++;
                        }
                    } elseif ($targetStatus === 'processing') {
                        if ($order->status === 'confirmed') {
                            $orderService->updateStatus($order, 'processing');
                            $count++;
                        } else {
                            $skipped++;
                        }
                    } elseif ($targetStatus === 'ready_to_ship') {
                        if ($order->status === 'processing') {
                            $carrier = $validated['carrier_name'] ?? 'Generic';
                            $tracking = $validated['tracking_no'] ?? ('TRK-' . strtoupper(Str::random(10)));
                            $inventoryService->readyToShipOrder($order, $carrier, $tracking);
                            $count++;
                        } else {
                            $skipped++;
                        }
                    } elseif ($targetStatus === 'dispatched') {
                        if ($order->status === 'ready_to_ship') {
                            $inventoryService->dispatchOrder($order);
                            $count++;
                        } else {
                            $skipped++;
                        }
                    } elseif ($targetStatus === 'delivered') {
                        if (in_array($order->status, ['dispatched', 'shipped'], true)) {
                            $inventoryService->deliverOrder($order);
                            $count++;
                        } else {
                            $skipped++;
                        }
                    } elseif ($targetStatus === 'cancelled') {
                        if (in_array($order->status, ['pending', 'confirmed', 'processing', 'ready_to_ship'], true)) {
                            $inventoryService->cancelOrder($order);
                            $count++;
                        } else {
                            $skipped++;
                        }
                    }
                } catch (\Exception $e) {
                    $skipped++;
                }
            }
        });

        $msg = "Bulk status update completed. Success: {$count}, Skipped: {$skipped}.";

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $msg]);
        }

        return back()->with($count > 0 ? 'success' : 'error', $msg);
    }

    public function destroy(Order $order)
    {
        $order->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Order deleted successfully.']);
        }

        return redirect()->route('orders')->with('success', 'Order deleted successfully.');
    }

    public function downloadInvoice(string $id, InvoiceService $invoiceService)
    {
        $order = Order::findOrFail($id);
        $invoice = $invoiceService->generateForOrder($order);

        $pdf = Pdf::loadView('orders.pdf.invoice', compact('invoice'))->setPaper('a5', 'portrait');
        return $pdf->download("invoice-{$invoice->invoice_no}.pdf");
    }

    public function generateInvoice(string $id, InvoiceService $invoiceService)
    {
        $order = Order::findOrFail($id);
        $invoiceService->generateForOrder($order);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json(['success' => true, 'message' => 'Invoice generated successfully.']);
        }

        return back()->with('success', 'Invoice generated successfully.');
    }

    public function downloadReceipt(string $id)
    {
        $order = Order::with(['party', 'items.product', 'shippingAddress.village'])->findOrFail($id);
        $pdf = Pdf::loadView('orders.pdf.cod', compact('order'))->setPaper('a5', 'portrait');
        return $pdf->download("receipt-{$order->order_no}.pdf");
    }

    public function bulkPrint(Request $request, InvoiceService $invoiceService)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
            'type' => 'required|string|in:invoice,cod',
        ]);

        $orders = Order::whereIn('id', $request->order_ids)->get();

        if ($request->type === 'invoice') {
            $invoices = [];
            foreach ($orders as $order) {
                $invoices[] = $invoiceService->generateForOrder($order);
            }
            $pdf = Pdf::loadView('orders.pdf.bulk_invoice', compact('invoices'))->setPaper('a5', 'portrait');
            return $pdf->download('bulk-invoices.pdf');
        } else {
            $pdf = Pdf::loadView('orders.pdf.bulk_cod', compact('orders'))->setPaper('a5', 'portrait');
            return $pdf->download('bulk-cod-receipts.pdf');
        }
    }

    public function bulkExport(Request $request)
    {
        $query = Order::with(['party', 'warehouse', 'items.product', 'shipments']);

        $user = auth()->user();
        if ($user && !$user->hasAnyRole(['Super Admin', 'Admin']) && !$user->can('view_all_order')) {
            $query->where('created_by', $user->id);
        }
        if ($user && $user->can('view_all_order') && !$user->hasAnyRole(['Super Admin', 'Admin'])) {
            $query->where('status', '!=', 'pending');
        }
        $this->applyOrderActionPermissionScope($query, $user);

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($subQuery) use ($s) {
                $subQuery->where('order_no', 'LIKE', "%{$s}%")
                    ->orWhereHas('party', function ($q) use ($s) {
                        $q->where('firstname', 'LIKE', "%{$s}%")
                            ->orWhere('lastname', 'LIKE', "%{$s}%")
                            ->orWhere('company_name', 'LIKE', "%{$s}%")
                            ->orWhere('phone', 'LIKE', "%{$s}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $requestedStatuses = array_filter(array_map('trim', explode(',', $request->status)));
            $query->whereIn('status', $requestedStatuses);
        }

        if ($request->filled('product')) {
            $productIds = array_filter(array_map('intval', explode(',', $request->product)));
            if (!empty($productIds)) {
                $query->whereHas('items', function ($q) use ($productIds) {
                    $q->whereIn('product_id', $productIds);
                });
            }
        }

        $orders = $query->orderBy('id')->get();

        $filename = 'orders-export-' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'order_no',
                'type',
                'status',
                'order_date',
                'party_name',
                'party_phone',
                'warehouse_code',
                'warehouse_name',
                'carrier_name',
                'tracking_no',
                'total_items'
            ]);

            foreach ($orders as $order) {
                $shipment = $order->shipments->first();
                $partyName = $order->party ? trim($order->party->firstname . ' ' . $order->party->lastname . ' ' . $order->party->company_name) : '';
                fputcsv($out, [
                    $order->order_no,
                    $order->type,
                    $order->status,
                    $order->order_date,
                    $partyName,
                    $order->party?->phone,
                    $order->warehouse?->code,
                    $order->warehouse?->name,
                    $shipment?->carrier_name,
                    $shipment?->tracking_no,
                    $order->items->count(),
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function bulkImport(Request $request, InventoryService $inventoryService)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
        ]);

        $isPreview = $request->boolean('preview');
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            if ($isPreview) return response()->json(['error' => 'Unable to read uploaded file.'], 400);
            return back()->with('error', 'Unable to read uploaded file.');
        }

        $firstRow = fgetcsv($handle);
        if ($firstRow === false) {
            fclose($handle);
            if ($isPreview) return response()->json(['error' => 'CSV file is empty.'], 400);
            return back()->with('error', 'CSV file is empty.');
        }

        $normalized = array_map(fn($v) => strtolower(trim((string)$v)), $firstRow);
        $hasHeader = in_array('order_no', $normalized, true) || in_array('order_id', $normalized, true);

        $updated = 0;
        $skipped = 0;
        $previewData = [];

        $extractByHeader = function (array $row, array $header, array $keys): ?string {
            foreach ($keys as $key) {
                $index = array_search($key, $header, true);
                if ($index !== false) {
                    return isset($row[$index]) ? trim((string)$row[$index]) : null;
                }
            }
            return null;
        };

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== false) {
                if ($hasHeader) {
                    $orderNo = $extractByHeader($row, $normalized, ['order_id', 'order_no']);
                    $carrierName = $extractByHeader($row, $normalized, ['carrier_name']);
                    $trackingNo = $extractByHeader($row, $normalized, ['tracking_no']);
                } else {
                    $orderNo = trim((string)($row[0] ?? ''));
                    $carrierName = trim((string)($row[1] ?? ''));
                    $trackingNo = trim((string)($row[2] ?? ''));
                }

                if (!$orderNo) {
                    continue;
                }

                $order = Order::with(['party', 'shipments'])->where('order_no', $orderNo)->orWhere('id', $orderNo)->first();

                if ($isPreview) {
                    $shipment = $order ? $order->shipments->first() : null;
                    $previewData[] = [
                        'order_no' => $orderNo,
                        'csv_carrier' => $carrierName ?: 'N/A',
                        'csv_tracking' => $trackingNo ?: 'N/A',
                        'is_valid' => $order && $order->status === 'processing',
                        'customer' => $order ? ($order->party ? trim($order->party->firstname . ' ' . $order->party->lastname) : 'N/A') : 'Not Found',
                        'current_status' => $order ? $order->status : 'Not Found',
                        'upcoming_status' => ($order && $order->status === 'processing') ? 'ready_to_ship' : 'N/A',
                        'existing_carrier' => $shipment && $shipment->carrier_name ? $shipment->carrier_name : 'N/A',
                        'existing_tracking' => $shipment && $shipment->tracking_no ? $shipment->tracking_no : 'N/A',
                    ];
                    continue;
                }

                if (!$order || $order->status !== 'processing') {
                    $skipped++;
                    continue;
                }

                $inventoryService->readyToShipOrder($order, $carrierName, $trackingNo);
                $updated++;
            }
            
            if ($isPreview) {
                DB::rollBack();
                fclose($handle);
                return response()->json(['preview' => $previewData]);
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            if ($isPreview) {
                return response()->json(['error' => 'Error processing CSV: ' . $e->getMessage()], 400);
            }
            return back()->with('error', 'Error processing CSV: ' . $e->getMessage());
        }

        fclose($handle);

        $message = "Orders import completed. Updated {$updated} order(s).";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} invalid/non-processing row(s).";
        }

        return back()->with('success', $message);
    }

    public function bulkImportTemplate()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['order_id', 'carrier_name', 'tracking_no']);
            fputcsv($out, ['1', 'FedEx', 'FDX123456789']);
            fclose($out);
        }, 'orders-shipping-import-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function revertStatus(Request $request, Order $order, InventoryService $inventoryService)
    {
        abort_unless(auth()->user()->can('orders.revert_status'), 403);

        $request->validate([
            'status' => 'required|string',
        ]);

        $targetStatus = $request->status;

        if ($targetStatus === 'pending' && in_array($order->status, ['confirmed', 'processing', 'cancelled', 'ready_to_ship'])) {
            $inventoryService->revertOrderToPending($order);
        } elseif ($targetStatus === 'confirmed' && $order->status === 'processing') {
            $order->loadMissing('shipments');
            foreach ($order->shipments as $shipment) {
                if ($shipment->status === 'pending') {
                    $shipment->events()->delete();
                    $shipment->delete();
                }
            }
            $order->update([
                'status' => 'confirmed',
                'updated_by' => auth()->id(),
            ]);
        } elseif ($targetStatus === 'processing' && $order->status === 'ready_to_ship') {
            $inventoryService->revertOrderToProcessing($order);
        } elseif ($targetStatus === 'ready_to_ship' && in_array($order->status, Order::inTransitStatuses(), true)) {
            $inventoryService->revertOrderToProcessing($order);
        } elseif ($targetStatus === 'dispatched' && $order->status === 'delivered') {
            $inventoryService->revertDeliveredToDispatched($order);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Order reverted successfully.']);
        }

        return back()->with('success', 'Order reverted successfully.');
    }

    private function applyOrderActionPermissionScope($query, $user): void
    {
        if (!$user) {
            $query->whereRaw('1 = 0');
            return;
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return;
        }

        $today = now()->toDateString();

        $query->where(function ($q) use ($user, $today) {
            $hasScope = false;

            $addScope = function (callable $callback) use ($q, &$hasScope) {
                $method = $hasScope ? 'orWhere' : 'where';
                $q->{$method}($callback);
                $hasScope = true;
            };

            if ($user->can('orders.confirm')) {
                $addScope(function ($sub) {
                    $sub->where('status', 'pending')
                        ->where(function ($draft) {
                            $draft->where('is_draft', false)->orWhereNull('is_draft');
                        });
                });
            }

            if ($user->can('orders.processing')) {
                $addScope(fn($sub) => $sub->where('status', 'confirmed'));
            }

            if ($user->can('orders.ship')) {
                $addScope(fn($sub) => $sub->whereIn('status', ['confirmed', 'processing']));
            }

            if ($user->can('orders.dispatch')) {
                $addScope(fn($sub) => $sub->where('status', 'ready_to_ship'));
            }

            if ($user->can('orders.deliver')) {
                $addScope(fn($sub) => $sub->whereIn('status', Order::inTransitStatuses()));
            }

            if ($user->can('orders.return')) {
                $addScope(fn($sub) => $sub->whereIn('status', ['delivered', 'dispatched', 'shipped']));
            }

            if ($user->can('orders.cancel')) {
                $addScope(fn($sub) => $sub->whereIn('status', ['pending', 'confirmed', 'processing', 'ready_to_ship']));
            }

            if ($user->can('orders.revert_status')) {
                $addScope(fn($sub) => $sub->whereIn('status', ['confirmed', 'processing', 'ready_to_ship', 'dispatched', 'shipped', 'delivered', 'returned', 'cancelled']));
            }

            $addScope(function ($sub) use ($user, $today) {
                $sub->where('created_by', $user->id)
                    ->whereDate('order_date', $today);
            });

            if (!$hasScope) {
                $q->whereRaw('1 = 0');
            }
        });
    }

    private function allowedOrderFilterStatuses($user): array
    {
        if (!$user) {
            return [];
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('view_all_order') || $user->can('orders.view')) {
            return ['future_order', 'pending', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'returned', 'cancelled'];
        }

        $statuses = [];

        if ($user->can('orders.confirm')) {
            $statuses[] = 'pending';
        }

        if ($user->can('orders.processing')) {
            $statuses[] = 'confirmed';
        }

        if ($user->can('orders.ship')) {
            array_push($statuses, 'confirmed', 'processing');
        }

        if ($user->can('orders.dispatch')) {
            $statuses[] = 'ready_to_ship';
        }

        if ($user->can('orders.deliver')) {
            $statuses[] = 'dispatched';
        }

        if ($user->can('orders.return')) {
            array_push($statuses, 'dispatched', 'delivered', 'returned');
        }

        if ($user->can('orders.cancel')) {
            array_push($statuses, 'future_order', 'pending', 'confirmed', 'processing', 'ready_to_ship');
        }

        if ($user->can('orders.revert_status')) {
            array_push($statuses, 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'returned', 'cancelled');
        }

        $orderedStatuses = ['future_order', 'pending', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'returned', 'cancelled'];

        return array_values(array_intersect($orderedStatuses, array_unique($statuses)));
    }
}
