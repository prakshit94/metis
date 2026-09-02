<?php

declare(strict_types=1);

namespace App\Modules\Customers\Controllers;

use App\Models\Crop;
use App\Models\IrrigationType;
use App\Models\LandUnit;
use App\Models\LeadSource;
use App\Modules\Catalog\Models\Category;
use App\Modules\Catalog\Models\Warehouse;
use App\Modules\Core\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\Party;
use App\Modules\Orders\Models\Coupon;
use App\Modules\Orders\Models\Offer;
use App\Modules\Orders\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:customer-view', only: ['index', 'show']),
            new Middleware('permission:customer-view|orders.create', only: ['searchByPhone']),
            new Middleware('permission:customer-create', only: ['store']),
            new Middleware('permission:customer-edit', only: ['update', 'toggleActive', 'bulkAction']),
            new Middleware('permission:customer-delete', only: ['destroy', 'forceDelete']),
            new Middleware('permission:customer-restore', only: ['restore']),
            new Middleware('permission:orders.create', only: ['placeOrder']),
        ];
    }

    /**
     * List customers with pagination, search, and status filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $sortMap = [
            'name' => 'firstname',
            'code' => 'party_code',
            'company' => 'company_name',
            'email' => 'email',
            'phone' => 'phone',
            'wallet' => 'wallet_balance',
            'limit' => 'credit_limit',
            'orders' => 'orders_count',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];

        $sortBy = $sortMap[$request->input('sort_by', 'updated_at')] ?? 'updated_at';
        $sortDir = strtolower((string) $request->input('sort_dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $deletedFilter = $request->input('deleted');

        $user = $request->user();
        $isGlobalView = $user && ($user->hasRole(['Super Admin', 'Admin']) || $user->can('view-all-data') || $user->can('view_all_customer'));

        $customers = Customer::query()
            ->when(! $isGlobalView, fn ($q) => $q->where('created_by', $user->id))
            ->when($deletedFilter === 'with', fn ($q) => $q->withTrashed())
            ->when($deletedFilter === 'only', fn ($q) => $q->onlyTrashed())
            ->with(['addresses.village'])
            ->withCount('orders')
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($inner) use ($request): void {
                    $term = '%'.$request->input('search').'%';
                    $inner->where('firstname', 'like', $term)
                        ->orWhere('middlename', 'like', $term)
                        ->orWhere('lastname', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('company_name', 'like', $term)
                        ->orWhere('party_code', 'like', $term);
                }),
            )
            ->when(
                $request->has('is_active'),
                fn ($q) => $q->where('is_active', (bool) $request->input('is_active')),
            )
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->input('status')),
            )
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);

        if (class_exists(\App\Modules\Orders\Models\Invoice::class)) {
            $customerIds = $customers->pluck('id')->toArray();
            
            if (!empty($customerIds)) {
                $allInvoices = \App\Modules\Orders\Models\Invoice::whereIn('order_id', function ($query) use ($customerIds) {
                    $query->select('id')->from('orders')->whereIn('party_id', $customerIds);
                })->whereIn('status', ['unpaid', 'partially_paid'])
                  ->with('order:id,party_id')
                  ->get();
                
                $customers->getCollection()->transform(function ($customer) use ($allInvoices) {
                    $due = 0;
                    $customerInvoices = $allInvoices->filter(function ($inv) use ($customer) {
                        return $inv->order && $inv->order->party_id === $customer->id;
                    });
                    foreach ($customerInvoices as $invoice) {
                        $due += $invoice->due_amount;
                    }
                    $customer->setAttribute('calculated_outstanding', (float) $due);
                    return $customer;
                });
            }
        }

        return response()->json($customers);
    }

    /**
     * Search customer by phone number.
     */
    public function searchByPhone(Request $request): JsonResponse
    {
        $phone = $request->input('phone');
        if (! $phone) {
            return response()->json(['found' => false]);
        }

        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }

        $user = $request->user();
        $isGlobalView = $user && ($user->hasRole(['Super Admin', 'Admin']) || $user->can('view-all-data') || $user->can('view_all_customer'));

        $query = Customer::where(function ($q) use ($phone) {
            $q->where('phone', $phone)
                ->orWhere('alternatemobile', $phone);
        });
        if (! $isGlobalView) {
            $query->where('created_by', $user->id);
        }

        $customer = $query->first();

        if ($customer) {
            return response()->json([
                'found' => true,
                'redirect' => route('orders.create', ['customer_id' => $customer->id]),
            ]);
        }

        return response()->json(['found' => false]);
    }

    /**
     * Store a new customer.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->filled('phone')) {
            $phone = preg_replace('/\D/', '', (string) $request->input('phone'));
            if (strlen($phone) > 10) {
                $phone = substr($phone, -10);
            }
            $request->merge(['phone' => $phone]);
        }

        $validated = $request->validate([
            'party_code' => ['nullable', 'string', 'max:50', 'unique:parties,party_code'],
            'firstname' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('parties', 'phone')->where(fn ($q) => $q->where('type', 'customer')->whereNull('deleted_at')),
            ],
            'alternatemobile' => ['nullable', 'string', 'max:20', 'different:phone'],
            'relative_name' => ['nullable', 'string', 'max:100'],
            'relative_phone' => ['nullable', 'string', 'max:20'],
            'source' => ['nullable', 'array'],
            'category' => ['nullable', 'string', 'max:50', 'in:individual,business'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'gst_no' => ['nullable', 'string', 'max:20'],
            'pan_no' => ['nullable', 'string', 'max:10'],
            'tax_no' => ['nullable', 'string', 'max:30'],
            'land_area' => ['nullable', 'numeric', 'min:0'],
            'land_unit' => ['nullable', 'string', 'max:20'],
            'crops' => ['nullable', 'array'],
            'irrigation_type' => ['nullable', 'array'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'outstanding_balance' => ['nullable', 'numeric'],
            'wallet_balance' => ['nullable', 'numeric'],
            'credit_valid_till' => ['nullable', 'date'],
            'aadhaar_last4' => ['nullable', 'digits:4'],
            'kyc_completed' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'is_active' => ['nullable', 'boolean'],
            'is_blacklisted' => ['nullable', 'boolean'],
            'internal_notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'referred_by_code' => ['nullable', 'string', 'exists:parties,referral_code'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $validated['type'] = 'customer';
        $validated['uuid'] = Str::uuid()->toString();
        $validated['party_code'] = $validated['party_code'] ?? 'CUST-'.strtoupper(Str::random(6));
        $validated['land_unit'] = $validated['land_unit'] ?? 'acre';
        $validated['credit_limit'] = $validated['credit_limit'] ?? 0.00;
        $validated['credit_days'] = $validated['credit_days'] ?? 0;
        $validated['outstanding_balance'] = $validated['outstanding_balance'] ?? 0.00;
        $validated['wallet_balance'] = $validated['wallet_balance'] ?? 0.00;
        $validated['kyc_completed'] = (bool) ($validated['kyc_completed'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['is_blacklisted'] = (bool) ($validated['is_blacklisted'] ?? false);

        if ($request->user()) {
            $validated['created_by'] = $request->user()->id;
        }

        // Handle referral
        if (! empty($validated['referred_by_code'])) {
            $referrer = Party::where('referral_code', $validated['referred_by_code'])->first();
            if ($referrer) {
                $validated['referred_by'] = $referrer->id;

                $source = $validated['source'] ?? [];
                if (! in_array('Referral', $source)) {
                    $source[] = 'Referral';
                }
                $validated['source'] = $source;
            }
        }
        unset($validated['referred_by_code']); // Unset it as it's not a field in the table

        // Generate referral code at creation time (not lazily on read)
        $validated['referral_code'] = strtoupper(Str::random(8));

        $customer = Customer::create($validated);

        return response()->json([
            'message' => "Customer [{$customer->name}] created successfully.",
            'data' => $customer,
        ], 201);
    }

    /**
     * Show a customer.
     */
    public function show(Request $request, int|string $customer)
    {
        $customer = Customer::withTrashed()
            ->withCount([
                'referrals as total_farmers_referred',
                'referredOrders as total_referred_orders_placed',
                'referredOrders as total_referred_orders_delivered' => function ($q) {
                    $q->where('orders.status', 'delivered');
                },
                'complaints as total_complaints',
                'complaints as active_complaints' => function ($q) {
                    $q->whereNotIn('status', ['resolved', 'closed']);
                },
            ])
            ->with([
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
                'callLogs' => function ($q) {
                    $q->latest()->limit(15)->with(['agent', 'tagL1', 'tagL2', 'tagL3', 'metas']);
                },
            ])
            ->findOrFail($customer);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'data' => $customer,
            ]);
        }

        $categories = Category::whereNull('parent_id')->get();
        $warehouses = Warehouse::with('village')->where('status', 'active')->get();
        $activeOffers = Offer::active()
            ->with('product:id,name,sku')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get();

        $activeCoupons = Coupon::where('is_active', true)->get();

        // Dynamic database parameters
        $crops = Crop::where('is_active', true)->get(['name']);
        $irrigationTypes = IrrigationType::where('is_active', true)->get(['name']);
        $landUnits = LandUnit::where('is_active', true)->get(['name']);
        $leadSources = LeadSource::where('is_active', true)->get(['name']);

        return view('customers.show', compact('customer', 'categories', 'warehouses', 'activeOffers', 'activeCoupons', 'crops', 'irrigationTypes', 'landUnits', 'leadSources'));
    }

    /**
     * Update a customer.
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        if ($request->filled('phone')) {
            $phone = preg_replace('/\D/', '', (string) $request->input('phone'));
            if (strlen($phone) > 10) {
                $phone = substr($phone, -10);
            }
            $request->merge(['phone' => $phone]);
        }

        $validated = $request->validate([
            'party_code' => ['nullable', 'string', 'max:50', 'unique:parties,party_code,'.$customer->id],
            'firstname' => ['required', 'string', 'max:100'],
            'middlename' => ['nullable', 'string', 'max:100'],
            'lastname' => ['required', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('parties', 'phone')->where(fn ($q) => $q->where('type', 'customer')->whereNull('deleted_at'))->ignore($customer->id),
            ],
            'alternatemobile' => ['nullable', 'string', 'max:20', 'different:phone'],
            'relative_name' => ['nullable', 'string', 'max:100'],
            'relative_phone' => ['nullable', 'string', 'max:20'],
            'source' => ['nullable', 'array'],
            'category' => ['nullable', 'string', 'max:50', 'in:individual,business'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'gst_no' => ['nullable', 'string', 'max:20'],
            'pan_no' => ['nullable', 'string', 'max:10'],
            'tax_no' => ['nullable', 'string', 'max:30'],
            'land_area' => ['nullable', 'numeric', 'min:0'],
            'land_unit' => ['nullable', 'string', 'max:20'],
            'crops' => ['nullable', 'array'],
            'irrigation_type' => ['nullable', 'array'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'credit_days' => ['nullable', 'integer', 'min:0'],
            'outstanding_balance' => ['nullable', 'numeric'],
            'wallet_balance' => ['nullable', 'numeric'],
            'credit_valid_till' => ['nullable', 'date'],
            'aadhaar_last4' => ['nullable', 'digits:4'],
            'kyc_completed' => ['nullable', 'boolean'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'is_active' => ['nullable', 'boolean'],
            'is_blacklisted' => ['nullable', 'boolean'],
            'internal_notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'avatar' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($customer->avatar) {
                Storage::disk('public')->delete($customer->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        if ($request->user()) {
            $validated['updated_by'] = $request->user()->id;
        }

        $validated['land_unit'] = $validated['land_unit'] ?? 'acre';

        $customer->update($validated);

        return response()->json([
            'message' => "Customer [{$customer->name}] updated successfully.",
            'data' => $customer->load('addresses.village'),
        ]);
    }

    /**
     * Soft delete a customer.
     */
    public function destroy(Request $request, int|string $customer): JsonResponse
    {
        $customer = Customer::withTrashed()->findOrFail($customer);

        if ($customer->trashed()) {
            return response()->json([
                'message' => "Customer [{$customer->name}] is already temporarily deleted.",
            ]);
        }

        $name = $customer->name;
        $customer->delete();

        return response()->json([
            'message' => "Customer [{$name}] temporarily deleted successfully.",
        ]);
    }

    /**
     * Restore a customer.
     */
    public function restore(Request $request, int|string $customer): JsonResponse
    {
        $customer = Customer::withTrashed()->findOrFail($customer);

        if (! $customer->trashed()) {
            return response()->json([
                'message' => "Customer [{$customer->name}] is not deleted.",
                'data' => $customer,
            ]);
        }

        $customer->restore();

        return response()->json([
            'message' => "Customer [{$customer->name}] restored successfully.",
            'data' => $customer,
        ]);
    }

    /**
     * Permanently delete a customer.
     */
    public function forceDelete(Request $request, int|string $customer): JsonResponse
    {
        abort_unless($request->user()?->can('customer-permanent-delete'), 403);
        $customer = Customer::withTrashed()->findOrFail($customer);

        $name = $customer->name;
        $customer->forceDelete();

        return response()->json([
            'message' => "Customer [{$name}] permanently deleted successfully.",
        ]);
    }

    /**
     * Toggle active status.
     */
    public function toggleActive(Request $request, Customer $customer): JsonResponse
    {
        $newState = ! $customer->is_active;

        $customer->update([
            'is_active' => $newState,
            'status' => $newState ? 'active' : 'inactive',
        ]);

        return response()->json([
            'message' => 'Customer account '.($newState ? 'activated' : 'deactivated').'.',
            'is_active' => $newState,
            'id' => $customer->id,
        ]);
    }

    /**
     * Bulk actions on selected IDs.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete,restore,force-delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:parties,id'],
        ]);

        $ids = $validated['ids'];
        $action = $validated['action'];

        if ($action === 'restore') {
            abort_unless($request->user()?->can('customer-restore'), 403);
            Customer::withTrashed()->whereIn('id', $ids)->get()->each(function (Customer $customer): void {
                if ($customer->trashed()) {
                    $customer->restore();
                }
            });

            return response()->json([
                'message' => count($ids).' customer(s) restored successfully.',
                'ids' => $ids,
            ]);
        }

        if ($action === 'delete') {
            abort_unless($request->user()?->can('customer-delete'), 403);
            Customer::whereIn('id', $ids)->get()->each->delete();

            return response()->json([
                'message' => count($ids).' customer(s) deleted successfully.',
                'deleted' => $ids,
            ]);
        }

        if ($action === 'force-delete') {
            abort_unless($request->user()?->can('customer-permanent-delete'), 403);
            Customer::withTrashed()->whereIn('id', $ids)->get()->each(function (Customer $customer): void {
                $customer->forceDelete();
            });

            return response()->json([
                'message' => count($ids).' customer(s) permanently deleted successfully.',
                'deleted' => $ids,
            ]);
        }

        $isActive = $action === 'activate';

        DB::transaction(function () use ($ids, $isActive) {
            Customer::whereIn('id', $ids)->get()->each(function (Customer $customer) use ($isActive) {
                $customer->update([
                    'is_active' => $isActive,
                    'status' => $isActive ? 'active' : 'inactive',
                ]);
            });
        });

        return response()->json([
            'message' => count($ids).' customer(s) '.($isActive ? 'activated' : 'deactivated').' successfully.',
            'is_active' => $isActive,
            'ids' => $ids,
        ]);
    }

    /**
     * Place order from customer profile page.
     */
    public function placeOrder(Request $request, Customer $customer, OrderService $orderService)
    {
        try {
            $data = $request->validate([
                'order_id' => [
                    'nullable',
                    Rule::exists('orders', 'id')->where('party_id', $customer->id),
                ],
                'cart' => 'required|string',
                'applied_offer_id' => 'nullable|exists:offers,id',
                'order_discount_amount' => 'nullable|numeric',
                'coupon_code' => 'nullable|string',
                'coupon_discount' => 'nullable|numeric',
                'tax_amount' => 'required|numeric',
                'subtotal' => 'required|numeric',
                'grand_total' => 'required|numeric',
                'warehouse_id' => 'required|exists:warehouses,id',
                'address_id' => [
                    'required',
                    Rule::exists('party_addresses', 'id')->where('party_id', $customer->id),
                ],
                'billing_address_id' => [
                    'nullable',
                    Rule::exists('party_addresses', 'id')->where('party_id', $customer->id),
                ],
                'status' => 'nullable|string|in:pending,future_order',
                'future_order_date' => 'required_if:status,future_order|nullable|date_format:Y-m-d',
            ]);

            // Map Customer to Party
            $party = Party::findOrFail($customer->id);

            if (! empty($data['order_id'])) {
                $order = Order::where('party_id', $customer->id)->findOrFail($data['order_id']);
                $orderService->updateCustomerOrder($order, $data);
                $msg = 'Order updated successfully!';
            } else {
                $order = $orderService->placeCustomerOrder($party, $data);
                $msg = 'Order placed successfully!';
            }

            return redirect()->route('customers.show', $customer)
                ->with('success', $msg)
                ->with('active_tab', 'history');
        } catch (ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?? $e->getMessage();

            return back()->with('error', $firstError);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process order: '.$e->getMessage());
        }
    }
}
