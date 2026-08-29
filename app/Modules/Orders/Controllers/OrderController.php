<?php

namespace App\Modules\Orders\Controllers;

use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\Service;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Core\Models\Village;
use App\Modules\Customers\Models\Party;
use App\Modules\Customers\Models\WalletTransaction;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Orders\Models\CancelReason;
use App\Modules\Orders\Models\Coupon;
use App\Modules\Orders\Models\DeliveryFailureReason;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Offer;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\RescheduleReason;
use App\Modules\Orders\Models\ReturnReason;
use App\Modules\Orders\Requests\StoreOrderRequest;
use App\Modules\Orders\Requests\UpdateOrderRequest;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:orders.view', only: ['index', 'show']),
            new Middleware('permission:orders.create', only: ['create', 'store', 'bulkImport', 'bulkImportTemplate']),
            new Middleware('permission:orders.edit', only: ['edit', 'update']),
            new Middleware('permission:orders.delete', only: ['destroy']),
            new Middleware('permission:orders.confirm', only: ['confirm']),
            new Middleware('permission:orders.ship', only: ['ship']),
            new Middleware('permission:orders.dispatch', only: ['dispatch']),
            new Middleware('permission:orders.processing', only: ['markProcessing']),
            new Middleware('permission:orders.deliver', only: ['markDelivered']),
            new Middleware('permission:orders.cancel', only: ['cancel']),
            new Middleware('permission:orders.return', only: ['markReturned']),
            new Middleware('permission:orders.invoice_pdf', only: ['downloadInvoice']),
            new Middleware('permission:orders.generate_invoice', only: ['generateInvoice', 'generateBulkInvoices']),
            new Middleware('permission:orders.cod', only: ['downloadReceipt']),
            new Middleware('permission:orders.receipt', only: ['receipt']),
            new Middleware('permission:orders.bulk_status', only: ['bulkStatus', 'bulkStoreVerification']),
            new Middleware('permission:orders.bulk_print', only: ['bulkPrint']),
            new Middleware('permission:orders.export', only: ['bulkExport', 'exportSelected']),
            new Middleware('permission:orders.revert_status', only: ['revertStatus']),
        ];
    }

    public function index(Request $request)
    {
        $query = Order::with([
            'party',
            'warehouse',
            'shippingAddress.village.services.providers:id,name,phone',
            'billingAddress.village',
            'invoice.payments',
            'items.product',
            'shipments.events',
            'payments',
            'creator',
            'updater',
            'orderReturns',
            'appliedOffer',
            'statusLogs' => fn ($q) => $q->with('user')->latest(),
        ])->withCount('items');

        $user = auth()->user();
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
            $hasPending = in_array('pending', $requestedStatuses, true);

            $realStatuses = array_values(array_filter($requestedStatuses, fn ($s) => ! in_array($s, ['future_order', 'pending'])));
            if (in_array('dispatched', $realStatuses, true)) {
                $realStatuses[] = 'shipped';
                $realStatuses = array_values(array_unique($realStatuses));
            }

            $query->where(function ($q) use ($hasFutureOrder, $hasPending, $realStatuses) {
                $first = true;

                if ($hasFutureOrder) {
                    $q->where('status', 'future_order');
                    $first = false;
                }

                if ($hasPending) {
                    $method = $first ? 'where' : 'orWhere';
                    $q->$method('status', 'pending');
                    $first = false;
                }

                if (! empty($realStatuses)) {
                    $method = $first ? 'whereIn' : 'orWhereIn';
                    $q->$method('status', $realStatuses);
                }
            });
        }

        if ($request->filled('product')) {
            $productIds = array_filter(array_map('intval', explode(',', $request->product)));
            if (! empty($productIds)) {
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
            if (! empty($carriers)) {
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

        if ($request->filled('warehouse')) {
            $query->where('warehouse_id', $request->warehouse);
        }

        // Stats Query (Cached)
        $statsQuery = clone $query;
        $statsVersion = Cache::get('order_stats_version', 0);
        $cacheKey = 'order_stats_'.$statsVersion.'_'.auth()->id().'_'.md5(json_encode($request->all()));
        $counts = Cache::remember($cacheKey, 300, function () use ($statsQuery) {
            $statsQuery->setEagerLoads([]);

            return $statsQuery->select([
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(orders.net_amount) as total_amount'),
                DB::raw("SUM(CASE WHEN orders.status = 'future_order' THEN 1 ELSE 0 END) as future_order"),
                DB::raw("SUM(CASE WHEN orders.status = 'future_order' THEN orders.net_amount ELSE 0 END) as future_order_amount"),
                DB::raw("SUM(CASE WHEN orders.status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN orders.status = 'pending' THEN orders.net_amount ELSE 0 END) as pending_amount"),
                DB::raw("SUM(CASE WHEN orders.status = 'pending_confirmation' THEN 1 ELSE 0 END) as pending_confirmation"),
                DB::raw("SUM(CASE WHEN orders.status = 'pending_confirmation' THEN orders.net_amount ELSE 0 END) as pending_confirmation_amount"),
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
                DB::raw("SUM(CASE WHEN orders.status = 'return_requested' THEN 1 ELSE 0 END) as return_requested"),
                DB::raw("SUM(CASE WHEN orders.status = 'return_requested' THEN orders.net_amount ELSE 0 END) as return_requested_amount"),
                DB::raw("SUM(CASE WHEN orders.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN orders.status = 'cancelled' THEN orders.net_amount ELSE 0 END) as cancelled_amount"),
            ])->toBase()->first();
        });

        $stats = [
            'total' => (int) ($counts->total ?? 0),
            'total_amount' => (float) ($counts->total_amount ?? 0),
            'future_order' => (int) ($counts->future_order ?? 0),
            'future_order_amount' => (float) ($counts->future_order_amount ?? 0),
            'pending' => (int) ($counts->pending ?? 0),
            'pending_amount' => (float) ($counts->pending_amount ?? 0),
            'pending_confirmation' => (int) ($counts->pending_confirmation ?? 0),
            'pending_confirmation_amount' => (float) ($counts->pending_confirmation_amount ?? 0),
            'confirmed' => (int) ($counts->confirmed ?? 0),
            'confirmed_amount' => (float) ($counts->confirmed_amount ?? 0),
            'processing' => (int) ($counts->processing ?? 0),
            'processing_amount' => (float) ($counts->processing_amount ?? 0),
            'ready_to_ship' => (int) ($counts->ready_to_ship ?? 0),
            'ready_to_ship_amount' => (float) ($counts->ready_to_ship_amount ?? 0),
            'dispatched' => (int) ($counts->dispatched ?? 0),
            'dispatched_amount' => (float) ($counts->dispatched_amount ?? 0),
            'delivered' => (int) ($counts->delivered ?? 0),
            'delivered_amount' => (float) ($counts->delivered_amount ?? 0),
            'returned' => (int) ($counts->returned ?? 0),
            'returned_amount' => (float) ($counts->returned_amount ?? 0),
            'return_requested' => (int) ($counts->return_requested ?? 0),
            'return_requested_amount' => (float) ($counts->return_requested_amount ?? 0),
            'cancelled' => (int) ($counts->cancelled ?? 0),
            'cancelled_amount' => (float) ($counts->cancelled_amount ?? 0),
        ];

        // Warehouse Stats Query (Cached)
        $warehouseStatsQuery = clone $query;
        $warehouseStatsCacheKey = 'order_warehouse_stats_'.$statsVersion.'_'.auth()->id().'_'.md5(json_encode($request->all()));
        $warehouseStats = Cache::remember($warehouseStatsCacheKey, 300, function () use ($warehouseStatsQuery) {
            // Remove with() eager loading to avoid issues with groupBy
            $warehouseStatsQuery->setEagerLoads([]);
            $stats = $warehouseStatsQuery->select([
                'warehouse_id',
                DB::raw('COUNT(orders.id) as total'),
                DB::raw('SUM(orders.net_amount) as total_amount'),
                DB::raw("SUM(CASE WHEN orders.status = 'future_order' THEN 1 ELSE 0 END) as future_order"),
                DB::raw("SUM(CASE WHEN orders.status = 'future_order' THEN orders.net_amount ELSE 0 END) as future_order_amount"),
                DB::raw("SUM(CASE WHEN orders.status = 'pending' THEN 1 ELSE 0 END) as pending"),
                DB::raw("SUM(CASE WHEN orders.status = 'pending' THEN orders.net_amount ELSE 0 END) as pending_amount"),
                DB::raw("SUM(CASE WHEN orders.status = 'pending_confirmation' THEN 1 ELSE 0 END) as pending_confirmation"),
                DB::raw("SUM(CASE WHEN orders.status = 'pending_confirmation' THEN orders.net_amount ELSE 0 END) as pending_confirmation_amount"),
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
                DB::raw("SUM(CASE WHEN orders.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled"),
                DB::raw("SUM(CASE WHEN orders.status = 'cancelled' THEN orders.net_amount ELSE 0 END) as cancelled_amount"),
                DB::raw("SUM(CASE WHEN orders.status = 'returned' THEN 1 ELSE 0 END) as returned"),
                DB::raw("SUM(CASE WHEN orders.status = 'returned' THEN orders.net_amount ELSE 0 END) as returned_amount"),
                DB::raw("SUM(CASE WHEN EXISTS(SELECT 1 FROM order_returns WHERE order_returns.order_id = orders.id AND order_returns.status NOT IN ('completed', 'rejected')) THEN 1 ELSE 0 END) as return_requested"),
                DB::raw("SUM(CASE WHEN EXISTS(SELECT 1 FROM order_returns WHERE order_returns.order_id = orders.id AND order_returns.status NOT IN ('completed', 'rejected')) THEN orders.net_amount ELSE 0 END) as return_requested_amount"),
            ])
                ->groupBy('warehouse_id')
                ->toBase()
                ->get();

            $warehouses = Warehouse::pluck('name', 'id')->toArray();

            return $stats->map(function ($item) use ($warehouses) {
                return [
                    'name' => $warehouses[$item->warehouse_id] ?? 'Unassigned',
                    'total' => (int) $item->total,
                    'total_amount' => (float) $item->total_amount,
                    'pending' => (int) $item->pending,
                    'pending_amount' => (float) $item->pending_amount,
                    'pending_confirmation' => (int) $item->pending_confirmation,
                    'pending_confirmation_amount' => (float) $item->pending_confirmation_amount,
                    'confirmed' => (int) $item->confirmed,
                    'confirmed_amount' => (float) $item->confirmed_amount,
                    'processing' => (int) $item->processing,
                    'processing_amount' => (float) $item->processing_amount,
                    'ready_to_ship' => (int) $item->ready_to_ship,
                    'ready_to_ship_amount' => (float) $item->ready_to_ship_amount,
                    'dispatched' => (int) $item->dispatched,
                    'dispatched_amount' => (float) $item->dispatched_amount,
                    'delivered' => (int) $item->delivered,
                    'delivered_amount' => (float) $item->delivered_amount,
                    'cancelled' => (int) $item->cancelled,
                    'cancelled_amount' => (float) $item->cancelled_amount,
                    'returned' => (int) $item->returned,
                    'returned_amount' => (float) $item->returned_amount,
                    'return_requested' => (int) $item->return_requested,
                    'return_requested_amount' => (float) $item->return_requested_amount,
                ];
            });
        });

        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'desc');
        if (in_array($sortField, ['id', 'order_no', 'net_amount', 'order_date', 'status'])) {
            $query->orderBy($sortField, $sortDirection === 'asc' ? 'asc' : 'desc');
        } else {
            $query->latest('id');
        }
        $perPage = $request->integer('limit', 10);
        $orders = $query->paginate($perPage);

        $productWarehousePairs = [];
        foreach ($orders as $order) {
            if ($order->status === 'pending') {
                foreach ($order->items as $item) {
                    if ($order->warehouse_id) {
                        $productWarehousePairs[] = [$item->product_id, $order->warehouse_id];
                    }
                }
            }
        }

        if (! empty($productWarehousePairs)) {
            $productIds = array_unique(array_column($productWarehousePairs, 0));
            $warehouseIds = array_unique(array_column($productWarehousePairs, 1));

            $stocks = Stock::whereIn('product_id', $productIds)
                ->whereIn('warehouse_id', $warehouseIds)
                ->get()
                ->keyBy(function ($stock) {
                    return $stock->product_id.'_'.$stock->warehouse_id;
                });

            foreach ($orders as $order) {
                if ($order->status === 'pending') {
                    $order->is_unfulfillable = false;
                    foreach ($order->items as $item) {
                        if ($order->warehouse_id) {
                            $key = $item->product_id.'_'.$order->warehouse_id;
                            $stock = $stocks->get($key);
                            $available = $stock ? ($stock->quantity - $stock->reserved_qty) : 0;
                            if ($item->quantity > $available) {
                                $item->is_out_of_stock = true;
                                $item->available_stock = $available;
                                $order->is_unfulfillable = true;
                            } else {
                                $item->is_out_of_stock = false;
                                $item->available_stock = $available;
                            }
                        }
                    }
                }
            }
        }

        $statusesList = $this->allowedOrderFilterStatuses($user);
        $productsList = Cache::remember('active_products_list', 3600, function () {
            return Product::where('status', '!=', 'draft')->orderBy('name')->get(['id', 'name', 'sku']);
        });

        $statesList = Cache::remember('geo_states', 3600, function () {
            return Village::distinct()->pluck('state_name')->filter()->sort()->values();
        });

        $districtsList = $request->filled('state') ? Cache::remember('geo_districts_'.md5($request->state), 3600, function () use ($request) {
            return Village::whereIn('state_name', array_map('trim', explode(',', $request->state)))
                ->distinct()->pluck('district_name')->filter()->sort()->values();
        }) : [];

        $talukasList = $request->filled('district') ? Cache::remember('geo_talukas_'.md5($request->state.'_'.$request->district), 3600, function () use ($request) {
            return Village::when($request->filled('state'), function ($q) use ($request) {
                $q->whereIn('state_name', array_map('trim', explode(',', $request->state)));
            })->whereIn('district_name', array_map('trim', explode(',', $request->district)))
                ->distinct()->pluck('taluka_name')->filter()->sort()->values();
        }) : [];

        $villagesList = $request->filled('taluka') ? Cache::remember('geo_villages_'.md5($request->state.'_'.$request->district.'_'.$request->taluka), 3600, function () use ($request) {
            return Village::when($request->filled('state'), function ($q) use ($request) {
                $q->whereIn('state_name', array_map('trim', explode(',', $request->state)));
            })->when($request->filled('district'), function ($q) use ($request) {
                $q->whereIn('district_name', array_map('trim', explode(',', $request->district)));
            })->whereIn('taluka_name', array_map('trim', explode(',', $request->taluka)))
                ->distinct()->pluck('village_name')->filter()->sort()->values();
        }) : [];

        $services = Cache::remember('active_services_list', 3600, function () {
            return Service::active()->get();
        });
        $carriersList = $services->pluck('name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $returnReasons = Cache::remember('active_return_reasons', 3600, fn () => ReturnReason::where('is_active', true)->orderBy('id')->get());
        $rescheduleReasons = Cache::remember('active_reschedule_reasons', 3600, fn () => RescheduleReason::where('is_active', true)->orderBy('id')->get());
        $deliveryFailureReasons = Cache::remember('active_delivery_failure_reasons', 3600, fn () => DeliveryFailureReason::where('is_active', true)->orderBy('id')->get());
        $cancelReasons = Cache::remember('active_cancel_reasons', 3600, fn () => CancelReason::where('is_active', true)->orderBy('id')->get());

        // 7 Day Trends Data (Cached)
        $trendsData = Cache::remember('order_trends_7_days_'.auth()->id(), 300, function () {
            $trendsQuery = Order::whereDate('order_date', '>=', now()->subDays(6))
                ->groupBy(DB::raw('DATE(order_date)'))
                ->orderBy(DB::raw('DATE(order_date)'))
                ->get([
                    DB::raw('DATE(order_date) as date'),
                    DB::raw('COUNT(*) as orders'),
                    DB::raw('SUM(net_amount) as revenue'),
                ]);

            $data = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = now()->subDays($i)->format('Y-m-d');
                $record = $trendsQuery->firstWhere('date', $date);
                $data[] = [
                    'date' => Carbon::parse($date)->format('D'),
                    'orders' => $record ? (int) $record->orders : 0,
                    'revenue' => $record ? (float) $record->revenue : 0,
                ];
            }

            return $data;
        });

        $warehousesList = Cache::remember('active_warehouses_list', 3600, fn () => Warehouse::where('status', 'active')->orderBy('name')->get(['id', 'name']));

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'orders' => $orders,
                'stats' => $stats,
                'warehouseStats' => $warehouseStats,
                'trends' => $trendsData,
                'allowed_filter_statuses' => $statusesList,
                'districts' => $districtsList,
                'talukas' => $talukasList,
                'villages' => $villagesList,
                'carriers' => $carriersList,
            ]);
        }

        return view('orders.index', compact(
            'orders',
            'stats',
            'statusesList',
            'productsList',
            'statesList',
            'districtsList',
            'talukasList',
            'villagesList',
            'services',
            'carriersList',
            'returnReasons',
            'rescheduleReasons',
            'deliveryFailureReasons',
            'cancelReasons',
            'warehousesList',
            'warehouseStats'
        ));
    }

    public function create()
    {
        $warehouses = Cache::remember('all_warehouses', 3600, fn () => Warehouse::orderBy('name')->get());
        $parties = Party::orderBy('firstname')->get();
        $activeOffers = Cache::remember('active_offers_with_products', 3600, fn () => Offer::with('product')->active()->orderByDesc('priority')->orderBy('id')->get());
        $activeCoupons = Cache::remember('active_coupons', 3600, fn () => Coupon::where('is_active', true)->get());
        $categories = Cache::remember('categories_with_children', 3600, fn () => Category::whereNull('parent_id')->with('children')->orderBy('name')->get());
        $initialCustomer = null;
        $initialOrder = null;

        if (request()->filled('customer_id')) {
            $initialCustomer = Party::with([
                'addresses.village.services',
                'referrals:id,firstname,lastname,phone,referred_by',
                'referrals.addresses' => function ($q) {
                    $q->where('is_default', true)->with('village:id,village_name,taluka_name,district_name');
                },
                'referrer' => function ($q) {
                    $q->withCount([
                        'referredOrders as total_referred_orders',
                        'referredOrders as delivered_referred_orders' => function ($query) {
                            $query->where('orders.status', 'delivered');
                        },
                    ]);
                },
                'callLogs' => function ($q) {
                    $q->latest()->limit(15)->with(['agent', 'tagL1', 'tagL2', 'tagL3', 'metas']);
                },
                'orders' => function ($q) {
                    $q->latest()->limit(10)->with([
                        'items.product:id,name,sku,image_path,tax_rate_id',
                        'items.product.taxRate',
                        'warehouse:id,name',
                        'shippingAddress:id,party_id,label,address_line_1,address_line_2,city,state,pincode',
                        'billingAddress:id,party_id,label,address_line_1,address_line_2,city,state,pincode',
                        'appliedOffer:id,name,discount_type,value',
                        'creator:id,first_name,last_name,name',
                    ]);
                },
            ])->withCount([
                'referrals as total_farmers_referred',
                'referredOrders as total_referred_orders_placed',
                'referredOrders as total_referred_orders_delivered' => function ($q) {
                    $q->where('orders.status', 'delivered');
                },
                'complaints as total_complaints',
                'complaints as active_complaints' => function ($q) {
                    $q->whereNotIn('status', ['resolved', 'closed']);
                },
            ])->find(request()->integer('customer_id'));

            if ($initialCustomer && empty($initialCustomer->referral_code)) {
                $initialCustomer->referral_code = strtoupper(Str::random(8));
                $initialCustomer->saveQuietly();
            }
        }

        if (request()->filled('order_id')) {
            $initialOrder = Order::with([
                'party.addresses.village.services',
                'warehouse',
                'items.product:id,name,sku,image_path,tax_rate_id',
                'items.product.taxRate',
                'shippingAddress.village.services',
                'billingAddress.village.services',
                'appliedOffer:id,name,discount_type,value',
                'creator:id,first_name,last_name,name',
                'statusLogs' => fn ($q) => $q->with('user')->latest(),
            ])->find(request()->integer('order_id'));

            if ($initialOrder && ! $initialCustomer) {
                $initialCustomer = Party::with([
                    'addresses.village.services',
                    'referrals:id,firstname,lastname,phone,referred_by',
                    'referrals.addresses' => function ($q) {
                        $q->where('is_default', true)->with('village:id,village_name,taluka_name,district_name');
                    },
                    'referrer' => function ($q) {
                        $q->withCount([
                            'referredOrders as total_referred_orders',
                            'referredOrders as delivered_referred_orders' => function ($query) {
                                $query->where('orders.status', 'delivered');
                            },
                        ]);
                    },
                    'orders' => function ($q) {
                        $q->latest()->limit(10)->with([
                            'items.product:id,name,sku,image_path,tax_rate_id',
                            'items.product.taxRate',
                            'warehouse:id,name',
                            'shippingAddress:id,party_id,label,address_line_1,address_line_2,city,state,pincode',
                            'billingAddress:id,party_id,label,address_line_1,address_line_2,city,state,pincode',
                            'appliedOffer:id,name,discount_type,value',
                            'creator:id,first_name,last_name,name',
                        ]);
                    },
                ])->withCount([
                    'referrals as total_farmers_referred',
                    'referredOrders as total_referred_orders_placed',
                    'referredOrders as total_referred_orders_delivered' => function ($q) {
                        $q->where('orders.status', 'delivered');
                    },
                    'complaints as total_complaints',
                    'complaints as active_complaints' => function ($q) {
                        $q->whereNotIn('status', ['resolved', 'closed']);
                    },
                ])->find($initialOrder->party_id);
            }
        }

        $hideSidebar = true;
        $lockSearch = true;
        $rescheduleReasons = Cache::remember('active_reschedule_reasons', 3600, fn () => RescheduleReason::where('is_active', true)->orderBy('id')->get());
        $cancelReasons = Cache::remember('active_cancel_reasons', 3600, fn () => CancelReason::where('is_active', true)->orderBy('id')->get());

        return view('orders.create', compact('warehouses', 'parties', 'activeOffers', 'activeCoupons', 'categories', 'hideSidebar', 'lockSearch', 'initialCustomer', 'initialOrder', 'rescheduleReasons', 'cancelReasons'));
    }

    public function store(StoreOrderRequest $request, OrderService $orderService)
    {
        $validated = $request->validated();

        $calc = $orderService->recalculateAndValidate($validated);
        $validated['items'] = $calc['items'];
        $validated['total_amount'] = $calc['subtotal'];
        $validated['tax_amount'] = $calc['tax_amount'];
        $validated['discount_amount'] = $calc['total_discount'];
        $validated['coupon_code'] = $calc['coupon_code'];
        $validated['applied_offer_id'] = $calc['applied_offer_id'];
        $validated['net_amount'] = $calc['grand_total'];
        $validated['applied_bogo_ids'] = $calc['applied_bogo_ids'];

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
        $order->load(['party', 'warehouse', 'items.product', 'shippingAddress', 'billingAddress', 'appliedOffer']);

        $order->party->loadCount([
            'complaints as total_complaints',
            'complaints as active_complaints' => function ($q) {
                $q->whereNotIn('status', ['resolved', 'closed']);
            },
        ]);

        $warehouses = Cache::remember('all_warehouses', 3600, fn () => Warehouse::orderBy('name')->get());
        $parties = Party::orderBy('firstname')->get();
        $categories = Cache::remember('categories_active', 3600, fn () => Category::where('is_active', true)->orderBy('name')->get());

        $activeOffers = Cache::remember('active_offers_priority', 3600, fn () => Offer::active()->orderByDesc('priority')->get());
        $activeCoupons = Cache::remember('active_coupons_valid', 3600, fn () => Coupon::active()->where(function ($q) {
            $q->whereNull('expiry_date')->orWhere('expiry_date', '>=', now()->startOfDay());
        })->get());

        $initialCustomer = $order->party;
        $initialOrder = $order;

        $hideSidebar = true;
        $lockSearch = true;
        $rescheduleReasons = Cache::remember('active_reschedule_reasons', 3600, fn () => RescheduleReason::where('is_active', true)->orderBy('id')->get());
        $cancelReasons = Cache::remember('active_cancel_reasons', 3600, fn () => CancelReason::where('is_active', true)->orderBy('id')->get());

        return view('orders.create', compact('warehouses', 'parties', 'activeOffers', 'activeCoupons', 'categories', 'hideSidebar', 'lockSearch', 'initialCustomer', 'initialOrder', 'rescheduleReasons', 'cancelReasons'));
    }

    public function show(string $id)
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
            'shippingAddress.village.services.providers:id,name,phone',
            'billingAddress.village.services',
            'appliedOffer',
            'statusLogs' => fn ($q) => $q->with('user')->latest(),
            'orderReturns',
        ]);

        $user = auth()->user();
        $this->applyOrderActionPermissionScope($query, $user);

        $order = $query->findOrFail($id);

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'order' => $order,
            ]);
        }

        return redirect()->route('orders');
    }

    public function confirm(string $id, Request $request, InventoryService $inventoryService)
    {
        $order = Order::findOrFail($id);
        if (! in_array($order->status, ['pending', 'pending_confirmation'])) {
            return response()->json(['error' => 'Only pending orders can be confirmed or scheduled.'], 400);
        }

        if ($request->input('action') === 'schedule') {
            $request->validate([
                'scheduled_date' => 'required|date',
            ]);

            $order->status = 'pending_confirmation';
            $order->scheduled_confirmation_date = $request->input('scheduled_date');
            $order->increment('confirmation_attempts');
            $order->save();

            $reasonText = $request->filled('reason') ? 'Reason: '.ucfirst(str_replace('_', ' ', $request->input('reason'))).'. ' : '';
            $scheduledDateText = 'Scheduled Date: '.Carbon::parse($request->input('scheduled_date'))->format('d M Y, h:i A').'. ';
            $notes = $request->filled('notes') ? $request->input('notes') : 'Scheduled for future confirmation.';

            $order->statusLogs()->create([
                'status' => 'pending_confirmation',
                'notes' => $reasonText.$scheduledDateText.$notes,
                'changed_by' => auth()->id(),
            ]);

            session()->flash('success', 'Order scheduled for confirmation.');

            return response()->json(['success' => true, 'message' => 'Order scheduled for confirmation.']);
        }

        try {
            $inventoryService->confirmOrder($order);

            $order->statusLogs()->create([
                'status' => 'confirmed',
                'notes' => $request->input('notes') ?? 'Order confirmed.',
                'changed_by' => auth()->id(),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->validator->errors()->all())->first()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        session()->flash('success', 'Order successfully confirmed!');

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
            'tracking_no' => 'required_unless:carrier_name,India Post|nullable|string|max:255',
        ]);

        try {
            $inventoryService->readyToShipOrder($order, $validated['carrier_name'], $validated['tracking_no'] ?? null);
            $order->statusLogs()->create([
                'status' => 'ready_to_ship',
                'notes' => 'Order marked as ready to ship. Carrier: '.$validated['carrier_name'].', Tracking: '.($validated['tracking_no'] ?? 'Auto-generated'),
                'changed_by' => auth()->id(),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->validator->errors()->all())->first()], 400);
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
        if (! $shipment || ! $shipment->carrier_name || ! $shipment->tracking_no) {
            return response()->json(['error' => 'Order cannot be dispatched without valid carrier and tracking details.'], 400);
        }

        try {
            $inventoryService->dispatchOrder($order);
            $order->statusLogs()->create([
                'status' => 'dispatched',
                'notes' => 'Order dispatched.',
                'changed_by' => auth()->id(),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->validator->errors()->all())->first()], 400);
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
            $order->statusLogs()->create([
                'status' => 'processing',
                'notes' => 'Order moved to processing.',
                'changed_by' => auth()->id(),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->validator->errors()->all())->first()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => 'Order moved to processing.']);
    }

    public function markDelivered(string $id, Request $request, InventoryService $inventoryService)
    {
        $order = Order::findOrFail($id);
        if (! in_array($order->status, ['dispatched', 'shipped'], true)) {
            return response()->json(['error' => 'Only dispatched orders can be marked as delivered.'], 400);
        }

        if ($request->input('action') === 'schedule') {
            $request->validate([
                'scheduled_date' => 'required|date',
            ]);

            $shipment = $order->shipments()->latest()->first();
            if ($shipment) {
                $shipment->next_followup_date = $request->input('scheduled_date');
                $shipment->reschedule_reason = $request->input('reason');
                $shipment->increment('delivery_attempts');
                $shipment->save();
            }

            $reasonText = $request->filled('reason') ? 'Reason: '.ucfirst(str_replace('_', ' ', $request->input('reason'))).'. ' : '';
            $scheduledDateText = 'Scheduled Date: '.Carbon::parse($request->input('scheduled_date'))->format('d M Y, h:i A').'. ';
            $notes = $request->filled('notes') ? $request->input('notes') : 'Scheduled for future delivery attempt.';

            $order->statusLogs()->create([
                'status' => 'delivery_rescheduled',
                'notes' => $reasonText.$scheduledDateText.$notes,
                'changed_by' => auth()->id(),
            ]);

            return response()->json(['success' => true, 'message' => 'Delivery attempt rescheduled.']);
        }

        try {
            $inventoryService->deliverOrder($order);

            $shipment = $order->shipments()->latest()->first();
            if ($shipment) {
                $shipment->delivered_by = $request->filled('delivered_by') ? $request->input('delivered_by') : auth()->user()->name;
                $shipment->save();
            }

            $order->statusLogs()->create([
                'status' => 'delivered',
                'notes' => $request->input('notes') ?? 'Order delivered.',
                'changed_by' => auth()->id(),
            ]);

            $this->applyCashback($order);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->validator->errors()->all())->first()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => 'Order marked as delivered.']);
    }

    public function cancel(string $id, Request $request, InventoryService $inventoryService)
    {
        $order = Order::findOrFail($id);
        if (in_array($order->status, ['delivered', 'cancelled', 'returned'], true)) {
            return response()->json(['error' => 'Delivered, cancelled, or returned orders cannot be cancelled.'], 400);
        }

        try {
            $inventoryService->cancelOrder($order);

            $reasonText = $request->filled('reason') ? 'Reason: '.ucfirst(str_replace('_', ' ', $request->input('reason'))).'. ' : '';
            $order->statusLogs()->create([
                'status' => 'cancelled',
                'notes' => $reasonText.($request->input('notes') ?? 'Order cancelled.'),
                'changed_by' => auth()->id(),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->validator->errors()->all())->first()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => 'Order cancelled and stock released.']);
    }

    public function markReturned(string $id, Request $request, InventoryService $inventoryService)
    {
        $order = Order::findOrFail($id);
        if (! in_array($order->status, ['delivered', 'dispatched', 'shipped'], true)) {
            return response()->json(['error' => 'Only delivered or dispatched orders can be marked as returned.'], 400);
        }

        try {
            $inventoryService->returnOrder($order);

            $reasonText = $request->filled('reason') ? 'Reason: '.ucfirst(str_replace('_', ' ', $request->input('reason'))).'. ' : '';
            $order->statusLogs()->create([
                'status' => 'returned',
                'notes' => $reasonText.($request->input('notes') ?? 'Order returned.'),
                'changed_by' => auth()->id(),
            ]);
        } catch (ValidationException $e) {
            return response()->json(['error' => collect($e->validator->errors()->all())->first()], 400);
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
            'carrier_name' => 'required_if:status,ready_to_ship|nullable|string|max:255',
            'tracking_no' => 'required_if:status,ready_to_ship|nullable|string|max:255',
        ]);

        $ids = $validated['order_ids'];
        $targetStatus = $validated['status'];
        $count = 0;
        $skipped = 0;
        $errors = [];

        DB::transaction(function () use ($ids, $targetStatus, $validated, $inventoryService, $orderService, &$count, &$skipped, &$errors) {
            $orders = Order::whereIn('id', $ids)->lockForUpdate()->get();

            foreach ($orders as $order) {
                try {
                    if ($targetStatus === 'confirmed') {
                        if (in_array($order->status, ['pending', 'pending_confirmation'])) {
                            $inventoryService->confirmOrder($order);
                            $order->statusLogs()->create([
                                'status' => 'confirmed',
                                'notes' => 'Bulk status updated to confirmed.',
                                'changed_by' => auth()->id(),
                            ]);
                            $count++;
                        } else {
                            $skipped++;
                        }
                    } elseif ($targetStatus === 'processing') {
                        if ($order->status === 'confirmed') {
                            $orderService->updateStatus($order, 'processing');
                            $order->statusLogs()->create([
                                'status' => 'processing',
                                'notes' => 'Bulk status updated to processing.',
                                'changed_by' => auth()->id(),
                            ]);
                            $count++;
                        } else {
                            $skipped++;
                        }
                    } elseif ($targetStatus === 'ready_to_ship') {
                        if ($order->status === 'processing') {
                            $inventoryService->readyToShipOrder($order, $validated['carrier_name'], $validated['tracking_no']);
                            $order->statusLogs()->create([
                                'status' => 'ready_to_ship',
                                'notes' => 'Bulk status updated to ready to ship. Carrier: '.$validated['carrier_name'].', Tracking: '.$validated['tracking_no'],
                                'changed_by' => auth()->id(),
                            ]);
                            $count++;
                        } else {
                            $skipped++;
                        }
                    } elseif ($targetStatus === 'dispatched') {
                        if ($order->status === 'ready_to_ship') {
                            $inventoryService->dispatchOrder($order);
                            $order->statusLogs()->create([
                                'status' => 'dispatched',
                                'notes' => 'Bulk status updated to dispatched.',
                                'changed_by' => auth()->id(),
                            ]);
                            $count++;
                        } else {
                            $skipped++;
                        }
                    } elseif ($targetStatus === 'delivered') {
                        if (in_array($order->status, ['dispatched', 'shipped'], true)) {
                            $inventoryService->deliverOrder($order);
                            $this->applyCashback($order);
                            $order->statusLogs()->create([
                                'status' => 'delivered',
                                'notes' => 'Bulk status updated to delivered.',
                                'changed_by' => auth()->id(),
                            ]);
                            $count++;
                        } else {
                            $skipped++;
                        }
                    } elseif ($targetStatus === 'cancelled') {
                        if (in_array($order->status, ['pending', 'pending_confirmation', 'confirmed', 'processing', 'ready_to_ship'], true)) {
                            $inventoryService->cancelOrder($order);
                            $order->statusLogs()->create([
                                'status' => 'cancelled',
                                'notes' => 'Bulk status updated to cancelled.',
                                'changed_by' => auth()->id(),
                            ]);
                            $count++;
                        } else {
                            $skipped++;
                        }
                    }
                } catch (ValidationException $e) {
                    $errors[] = "Order #{$order->order_no}: ".collect($e->validator->errors()->all())->first();
                    $skipped++;
                } catch (\Exception $e) {
                    $errors[] = "Order #{$order->order_no}: ".$e->getMessage();
                    $skipped++;
                }
            }
        });

        $msg = "Bulk status update completed. Success: {$count}, Skipped: {$skipped}.";
        if (! empty($errors)) {
            // Include up to 3 errors to avoid excessively long toast messages
            $msg .= ' Errors: '.implode(' ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $msg .= ' (and '.(count($errors) - 3).' more)';
            }
        }

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
        $order = Order::with(['party', 'items.product.taxRate', 'shippingAddress.village', 'billingAddress.village'])->findOrFail($id);
        $invoice = $invoiceService->generateForOrder($order);
        $invoice->load(['order.party', 'order.items.product.taxRate', 'order.shippingAddress.village', 'order.billingAddress.village']);

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

    public function generateBulkInvoices(Request $request, InvoiceService $invoiceService)
    {
        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id',
        ]);

        $orders = Order::whereIn('id', array_unique($validated['order_ids']))->get();
        $generated = 0;

        DB::transaction(function () use ($orders, $invoiceService, &$generated): void {
            foreach ($orders as $order) {
                if (! $invoiceService->findForOrder($order)) {
                    $invoiceService->generateForOrder($order);
                    $generated++;
                }
            }
        });

        $message = $generated > 0
            ? "Generated {$generated} invoice(s)."
            : 'Invoices have already been generated for the selected orders.';

        return response()->json(['success' => true, 'message' => $message, 'generated' => $generated]);
    }

    public function downloadReceipt(string $id, InvoiceService $invoiceService)
    {
        $order = Order::with(['party', 'items.product.taxRate', 'shippingAddress.village', 'invoice'])->findOrFail($id);
        $invoiceService->generateForOrder($order);
        $order->load('invoice');

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

        $orders = Order::whereIn('id', array_unique($request->order_ids))
            ->with('invoice')
            ->get();

        DB::transaction(function () use ($orders, $invoiceService): void {
            foreach ($orders as $order) {
                if (! $invoiceService->findForOrder($order)) {
                    $invoiceService->generateForOrder($order);
                }
            }
        });

        $orders->load('invoice');

        if ($request->type === 'invoice') {
            $invoices = Invoice::with([
                'order.party',
                'order.warehouse',
                'order.items.product.taxRate',
                'order.shippingAddress.village',
                'order.billingAddress.village',
            ])->whereIn('order_id', $orders->pluck('id'))->get();
            $pdf = Pdf::loadView('orders.pdf.bulk_invoice', compact('invoices'))->setPaper('a5', 'portrait');

            return $pdf->download('bulk-invoices.pdf');
        } else {
            $orders->load([
                'party',
                'items.product.taxRate',
                'shippingAddress.village',
                'warehouse',
            ]);
            $pdf = Pdf::loadView('orders.pdf.bulk_cod', compact('orders'))->setPaper('a5', 'portrait');

            return $pdf->download('bulk-cod-receipts.pdf');
        }
    }

    public function update(UpdateOrderRequest $request, Order $order, OrderService $orderService)
    {
        $validated = $request->validated();

        $updated = $orderService->updateCustomerOrder($order, $validated);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Order updated successfully.',
                'order' => $updated,
            ]);
        }

        return redirect()->route('orders')->with('success', 'Order updated successfully.');
    }

    public function bulkExport(Request $request)
    {
        $query = Order::with(['party', 'warehouse', 'items.product', 'shipments', 'billingAddress', 'shippingAddress']);

        $user = auth()->user();
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
            if (! empty($productIds)) {
                $query->whereHas('items', function ($q) use ($productIds) {
                    $q->whereIn('product_id', $productIds);
                });
            }
        }

        $orders = $query->orderBy('id')->get();

        $filename = 'orders-export-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload($this->generateCsvExportCallback($orders), $filename, [
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
            if ($isPreview) {
                return response()->json(['error' => 'Unable to read uploaded file.'], 400);
            }

            return back()->with('error', 'Unable to read uploaded file.');
        }

        $firstRow = fgetcsv($handle);
        if ($firstRow === false) {
            fclose($handle);
            if ($isPreview) {
                return response()->json(['error' => 'CSV file is empty.'], 400);
            }

            return back()->with('error', 'CSV file is empty.');
        }

        $normalized = array_map(fn ($v) => strtolower(trim((string) $v)), $firstRow);
        $hasHeader = in_array('order_no', $normalized, true) || in_array('order_id', $normalized, true);

        $updated = 0;
        $skipped = 0;
        $previewData = [];

        $extractByHeader = function (array $row, array $header, array $keys): ?string {
            foreach ($keys as $key) {
                $index = array_search($key, $header, true);
                if ($index !== false) {
                    return isset($row[$index]) ? trim((string) $row[$index]) : null;
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
                    $orderNo = trim((string) ($row[0] ?? ''));
                    $carrierName = trim((string) ($row[1] ?? ''));
                    $trackingNo = trim((string) ($row[2] ?? ''));
                }

                if (! $orderNo) {
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
                        'customer' => $order ? ($order->party ? trim($order->party->firstname.' '.$order->party->lastname) : 'N/A') : 'Not Found',
                        'current_status' => $order ? $order->status : 'Not Found',
                        'upcoming_status' => ($order && $order->status === 'processing') ? 'ready_to_ship' : 'N/A',
                        'existing_carrier' => $shipment && $shipment->carrier_name ? $shipment->carrier_name : 'N/A',
                        'existing_tracking' => $shipment && $shipment->tracking_no ? $shipment->tracking_no : 'N/A',
                    ];

                    continue;
                }

                if (! $order || $order->status !== 'processing') {
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
                return response()->json(['error' => 'Error processing CSV: '.$e->getMessage()], 400);
            }

            return back()->with('error', 'Error processing CSV: '.$e->getMessage());
        }

        fclose($handle);

        $message = "Orders import completed. Updated {$updated} order(s).";
        if ($skipped > 0) {
            $message .= " Skipped {$skipped} invalid/non-processing row(s).";
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json(['success' => true, 'message' => $message]);
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

    public function exportSelected(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:orders,id',
        ]);

        $user = auth()->user();

        // Apply the same permission scope used in index() — prevents data-leak via ID enumeration
        $query = Order::with(['party', 'warehouse', 'items.product', 'shipments', 'billingAddress', 'shippingAddress'])
            ->whereIn('id', $validated['ids'])
            ->orderBy('id');

        $this->applyOrderActionPermissionScope($query, $user);

        $orders = $query->get();

        $filename = 'orders-export-selected-'.now()->format('Ymd_His').'.csv';

        return response()->streamDownload($this->generateCsvExportCallback($orders), $filename, [
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
            \DB::transaction(function () use ($order) {
                $order->loadMissing('shipments');
                foreach ($order->shipments as $shipment) {
                    if ($shipment->status === 'pending') {
                        $shipment->events()->get()->each->delete();
                        $shipment->delete();
                    }
                }
                $order->update([
                    'status' => 'confirmed',
                    'updated_by' => auth()->id(),
                ]);
            });
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
        if (! $user) {
            $query->whereRaw('1 = 0');

            return;
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('view-all-data')) {
            return;
        }

        if ($user->can('view_all_order')) {
            $allowedStatuses = $this->allowedOrderFilterStatuses($user);
            if (! empty($allowedStatuses)) {
                $query->where(function ($q) use ($user, $allowedStatuses) {
                    $q->where('created_by', $user->id)
                        ->orWhereIn('status', $allowedStatuses);
                });
            } else {
                $query->where('created_by', $user->id);
            }

            return;
        }

        $query->where('created_by', $user->id);
    }

    private function allowedOrderFilterStatuses($user): array
    {
        if (! $user) {
            return [];
        }

        if ($user->hasAnyRole(['Super Admin', 'Admin']) || $user->can('view-all-data')) {
            return ['future_order', 'pending', 'pending_confirmation', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'return_requested', 'returned', 'cancelled'];
        }

        $statuses = [];

        if ($user->can('orders.view.future_order')) {
            $statuses[] = 'future_order';
        }
        if ($user->can('orders.view.pending')) {
            $statuses[] = 'pending';
            $statuses[] = 'pending_confirmation';
        }
        if ($user->can('orders.view.confirmed')) {
            $statuses[] = 'confirmed';
        }
        if ($user->can('orders.view.processing')) {
            $statuses[] = 'processing';
        }
        if ($user->can('orders.view.ready_to_ship')) {
            $statuses[] = 'ready_to_ship';
        }
        if ($user->can('orders.view.dispatched')) {
            $statuses[] = 'dispatched';
        }
        if ($user->can('orders.view.delivered')) {
            $statuses[] = 'delivered';
        }
        if ($user->can('orders.view.return_requested')) {
            $statuses[] = 'return_requested';
        }
        if ($user->can('orders.view.returned')) {
            $statuses[] = 'returned';
        }
        if ($user->can('orders.view.cancelled')) {
            $statuses[] = 'cancelled';
        }

        $orderedStatuses = ['future_order', 'pending', 'pending_confirmation', 'confirmed', 'processing', 'ready_to_ship', 'dispatched', 'delivered', 'return_requested', 'returned', 'cancelled'];

        if ($user->can('view_all_order') && empty($statuses)) {
            return $orderedStatuses;
        }

        return array_values(array_intersect($orderedStatuses, array_unique($statuses)));
    }

    private function applyCashback(Order $order): void
    {
        if (! $order->party_id || $order->cashback_earned > 0) {
            return; // Already earned or no customer
        }

        $cashbackEarned = 0;

        if ($order->applied_offer_id) {
            $offer = Offer::find($order->applied_offer_id);
            if ($offer) {
                if ($offer->cashback_percent && $offer->cashback_percent > 0) {
                    $cashbackEarned += ($order->net_amount * ($offer->cashback_percent / 100));
                }
                if ($offer->cashback_fixed && $offer->cashback_fixed > 0) {
                    $cashbackEarned += $offer->cashback_fixed;
                }
            }
        }

        if ($order->coupon_code) {
            $coupon = Coupon::where('code', $order->coupon_code)->first();
            if ($coupon) {
                if ($coupon->cashback_percent && $coupon->cashback_percent > 0) {
                    $cashbackEarned += ($order->net_amount * ($coupon->cashback_percent / 100));
                }
                if ($coupon->cashback_fixed && $coupon->cashback_fixed > 0) {
                    $cashbackEarned += $coupon->cashback_fixed;
                }
            }
        }

        if ($cashbackEarned > 0) {
            $order->cashback_earned = $cashbackEarned;
            $order->saveQuietly();

            $party = Party::find($order->party_id);
            if ($party) {
                $party->wallet_balance += $cashbackEarned;
                $party->saveQuietly();

                WalletTransaction::create([
                    'party_id' => $party->id,
                    'amount' => $cashbackEarned,
                    'type' => 'credit',
                    'reference_type' => 'order',
                    'reference_id' => $order->id,
                    'description' => 'Cashback earned for order #'.$order->order_no,
                    'created_by' => auth()->id() ?? $order->created_by,
                ]);
            }
        }
    }

    private function generateCsvExportCallback($orders)
    {
        return function () use ($orders) {
            $out = fopen('php://output', 'w');

            fputcsv($out, [
                'Order ID', 'Order No', 'Order Date', 'Status', 'Order Type',
                'Order Subtotal', 'Order Tax', 'Order Discount', 'Order Total',
                'Customer Name', 'Customer Email', 'Customer Phone',
                'Billing Address 1', 'Billing Address 2', 'Billing Village', 'Billing PO/BO', 'Billing Taluka', 'Billing District', 'Billing City', 'Billing State', 'Billing Pincode',
                'Shipping Address 1', 'Shipping Address 2', 'Shipping Village', 'Shipping PO/BO', 'Shipping Taluka', 'Shipping District', 'Shipping City', 'Shipping State', 'Shipping Pincode',
                'Warehouse Name', 'Carrier Name', 'Tracking No',
                'Product Name', 'Product SKU', 'Quantity', 'Unit Price', 'Item Tax', 'Item Discount', 'Item Net',
            ]);

            foreach ($orders as $order) {
                $shipment = $order->shipments->first();
                $customerName = $order->party ? trim($order->party->firstname.' '.$order->party->lastname) : '';
                $customerEmail = $order->party?->email ?? '';
                $customerPhone = $order->party?->phone ?? '';

                $billingAdd1 = $order->billing_address_line_1 ?? '';
                $billingAdd2 = $order->billing_address_line_2 ?? '';
                $billingVillage = $order->billing_village_name ?? '';
                $billingPO = $order->billing_post_office ?? '';
                $billingTaluka = $order->billing_taluka ?? '';
                $billingDistrict = $order->billing_district ?? '';
                $billingCity = $order->billing_city ?? '';
                $billingState = $order->billing_state ?? '';
                $billingPin = $order->billing_pincode ?? '';

                $shippingAdd1 = $order->shipping_address_line_1 ?? '';
                $shippingAdd2 = $order->shipping_address_line_2 ?? '';
                $shippingVillage = $order->shipping_village_name ?? '';
                $shippingPO = $order->shipping_post_office ?? '';
                $shippingTaluka = $order->shipping_taluka ?? '';
                $shippingDistrict = $order->shipping_district ?? '';
                $shippingCity = $order->shipping_city ?? '';
                $shippingState = $order->shipping_state ?? '';
                $shippingPin = $order->shipping_pincode ?? '';

                $warehouseName = $order->warehouse?->name ?? '';
                $carrierName = $shipment?->carrier_name ?? '';
                $trackingNo = $shipment?->tracking_no ?? '';

                if ($order->items->isEmpty()) {
                    fputcsv($out, [
                        $order->id, $order->order_no, $order->order_date, $order->status, $order->type,
                        $order->total_amount, $order->tax_amount, $order->discount_amount, $order->net_amount,
                        $customerName, $customerEmail, $customerPhone,
                        $billingAdd1, $billingAdd2, $billingVillage, $billingPO, $billingTaluka, $billingDistrict, $billingCity, $billingState, $billingPin,
                        $shippingAdd1, $shippingAdd2, $shippingVillage, $shippingPO, $shippingTaluka, $shippingDistrict, $shippingCity, $shippingState, $shippingPin,
                        $warehouseName, $carrierName, $trackingNo,
                        '', '', '', '', '', '', '',
                    ]);
                } else {
                    foreach ($order->items as $item) {
                        $productName = $item->product ? $item->product->name : 'Unknown Product';
                        $productSku = $item->product ? $item->product->sku : '';
                        fputcsv($out, [
                            $order->id, $order->order_no, $order->order_date, $order->status, $order->type,
                            $order->total_amount, $order->tax_amount, $order->discount_amount, $order->net_amount,
                            $customerName, $customerEmail, $customerPhone,
                            $billingAdd1, $billingAdd2, $billingVillage, $billingPO, $billingTaluka, $billingDistrict, $billingCity, $billingState, $billingPin,
                            $shippingAdd1, $shippingAdd2, $shippingVillage, $shippingPO, $shippingTaluka, $shippingDistrict, $shippingCity, $shippingState, $shippingPin,
                            $warehouseName, $carrierName, $trackingNo,
                            $productName, $productSku, $item->quantity, $item->unit_price, $item->tax_amount, $item->discount_amount, $item->total_amount,
                        ]);
                    }
                }
            }

            fclose($out);
        };
    }
}
