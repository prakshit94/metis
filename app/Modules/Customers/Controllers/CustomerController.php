<?php

declare(strict_types=1);

namespace App\Modules\Customers\Controllers;

use App\Modules\Core\Controllers\Controller;
use App\Modules\Customers\Models\Customer;
use App\Modules\Customers\Models\Party;
use App\Modules\Orders\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class CustomerController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:customer-view', only: ['index', 'show', 'searchByPhone']),
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
            'name'       => 'firstname',
            'email'      => 'email',
            'phone'      => 'phone',
            'created_at' => 'created_at',
            'updated_at' => 'updated_at',
        ];
        
        $sortBy = $sortMap[$request->input('sort_by', 'name')] ?? 'firstname';
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $perPage = min(max((int) $request->input('per_page', 15), 1), 100);

        $deletedFilter = $request->input('deleted');

        $customers = Customer::query()
            ->when($deletedFilter === 'with', fn ($q) => $q->withTrashed())
            ->when($deletedFilter === 'only', fn ($q) => $q->onlyTrashed())
            ->with(['addresses.village'])
            ->when(
                $request->filled('search'),
                fn ($q) => $q->where(function ($inner) use ($request): void {
                    $term = '%' . $request->input('search') . '%';
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

        return response()->json($customers);
    }

    /**
     * Search customer by phone number.
     */
    public function searchByPhone(Request $request): JsonResponse
    {
        $phone = $request->input('phone');
        if (!$phone) {
            return response()->json(['found' => false]);
        }

        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) > 10) {
            $phone = substr($phone, -10);
        }

        $customer = Customer::where('phone', $phone)->first();
        
        if ($customer) {
            return response()->json([
                'found' => true,
                'redirect' => route('orders.create', ['customer_id' => $customer->id])
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
            'party_code'       => ['nullable', 'string', 'max:50', 'unique:parties,party_code'],
            'firstname'        => ['required', 'string', 'max:100'],
            'middlename'       => ['nullable', 'string', 'max:100'],
            'lastname'         => ['required', 'string', 'max:100'],
            'email'            => ['nullable', 'email', 'max:255'],
            'phone'            => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('parties', 'phone')->where(fn($q) => $q->where('type', 'customer')->whereNull('deleted_at'))
            ],
            'alternatemobile'  => ['nullable', 'string', 'max:20'],
            'relative_mobile'  => ['nullable', 'string', 'max:20'],
            'phone_number_2'   => ['nullable', 'string', 'max:20'],
            'relative_phone'   => ['nullable', 'string', 'max:20'],
            'source'           => ['nullable', 'array'],
            'category'         => ['nullable', 'string', 'max:50', 'in:individual,business'],
            'company_name'     => ['nullable', 'string', 'max:255'],
            'gst_no'           => ['nullable', 'string', 'max:20'],
            'pan_no'           => ['nullable', 'string', 'max:10'],
            'tax_no'           => ['nullable', 'string', 'max:30'],
            'land_area'        => ['nullable', 'numeric', 'min:0'],
            'land_unit'        => ['nullable', 'string', 'max:20'],
            'crops'            => ['nullable', 'array'],
            'irrigation_type'  => ['nullable', 'array'],
            'credit_limit'     => ['nullable', 'numeric', 'min:0'],
            'credit_days'      => ['nullable', 'integer', 'min:0'],
            'outstanding_balance' => ['nullable', 'numeric'],
            'credit_valid_till'=> ['nullable', 'date'],
            'aadhaar_last4'    => ['nullable', 'digits:4'],
            'kyc_completed'    => ['nullable', 'boolean'],
            'status'           => ['required', 'in:active,inactive,suspended'],
            'is_active'        => ['nullable', 'boolean'],
            'is_blacklisted'   => ['nullable', 'boolean'],
            'internal_notes'   => ['nullable', 'string'],
            'tags'             => ['nullable', 'array'],
        ]);

        $validated['type'] = 'customer';
        $validated['uuid'] = Str::uuid()->toString();
        $validated['party_code'] = $validated['party_code'] ?? 'CUST-' . strtoupper(Str::random(6));
        $validated['land_unit'] = $validated['land_unit'] ?? 'acre';
        $validated['credit_limit'] = $validated['credit_limit'] ?? 0.00;
        $validated['credit_days'] = $validated['credit_days'] ?? 0;
        $validated['outstanding_balance'] = $validated['outstanding_balance'] ?? 0.00;
        $validated['kyc_completed'] = (bool) ($validated['kyc_completed'] ?? false);
        $validated['is_active'] = (bool) ($validated['is_active'] ?? true);
        $validated['is_blacklisted'] = (bool) ($validated['is_blacklisted'] ?? false);

        if ($request->user()) {
            $validated['created_by'] = $request->user()->id;
        }

        $customer = Customer::create($validated);

        return response()->json([
            'message' => "Customer [{$customer->name}] created successfully.",
            'data'    => $customer,
        ], 201);
    }

    /**
     * Show a customer.
     */
    public function show(Request $request, int|string $customer)
    {
        $customer = Customer::withTrashed()
            ->with([
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
            ])
            ->findOrFail($customer);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'data' => $customer,
            ]);
        }

        $categories = \Illuminate\Support\Facades\Cache::remember('categories_parent_null', 600, fn() => \App\Modules\Catalog\Models\Category::whereNull('parent_id')->get());
        $warehouses = \Illuminate\Support\Facades\Cache::remember('warehouses_active_village', 600, fn() => \App\Modules\Catalog\Models\Warehouse::with('village')->where('status', 'active')->get());
        $activeOffers = \Illuminate\Support\Facades\Cache::remember('active_offers_with_product', 600, fn() => \App\Modules\Orders\Models\Offer::active()
            ->with('product:id,name,sku')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get());
        
        // Static fallbacks for agricultures parameters
        $crops = collect(['Wheat', 'Rice', 'Cotton', 'Sugarcane', 'Maize', 'Soybean', 'Gram', 'Mustard', 'Bajra', 'Jowar'])->map(fn($name) => (object) ['name' => $name]);
        $irrigationTypes = collect(['Drip', 'Sprinkler', 'Canal', 'Tube Well', 'Rainfed', 'River Pump'])->map(fn($name) => (object) ['name' => $name]);
        $landUnits = collect(['Acre', 'Hectare', 'Bigha', 'Guntha', 'Kanal', 'Marla'])->map(fn($name) => (object) ['name' => $name]);

        return view('customers.show', compact('customer', 'categories', 'warehouses', 'activeOffers', 'crops', 'irrigationTypes', 'landUnits'));
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
            'party_code'       => ['nullable', 'string', 'max:50', 'unique:parties,party_code,' . $customer->id],
            'firstname'        => ['required', 'string', 'max:100'],
            'middlename'       => ['nullable', 'string', 'max:100'],
            'lastname'         => ['required', 'string', 'max:100'],
            'email'            => ['nullable', 'email', 'max:255'],
            'phone'            => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('parties', 'phone')->where(fn($q) => $q->where('type', 'customer')->whereNull('deleted_at'))->ignore($customer->id)
            ],
            'alternatemobile'  => ['nullable', 'string', 'max:20'],
            'relative_mobile'  => ['nullable', 'string', 'max:20'],
            'phone_number_2'   => ['nullable', 'string', 'max:20'],
            'relative_phone'   => ['nullable', 'string', 'max:20'],
            'source'           => ['nullable', 'array'],
            'category'         => ['nullable', 'string', 'max:50', 'in:individual,business'],
            'company_name'     => ['nullable', 'string', 'max:255'],
            'gst_no'           => ['nullable', 'string', 'max:20'],
            'pan_no'           => ['nullable', 'string', 'max:10'],
            'tax_no'           => ['nullable', 'string', 'max:30'],
            'land_area'        => ['nullable', 'numeric', 'min:0'],
            'land_unit'        => ['nullable', 'string', 'max:20'],
            'crops'            => ['nullable', 'array'],
            'irrigation_type'  => ['nullable', 'array'],
            'credit_limit'     => ['nullable', 'numeric', 'min:0'],
            'credit_days'      => ['nullable', 'integer', 'min:0'],
            'outstanding_balance' => ['nullable', 'numeric'],
            'credit_valid_till'=> ['nullable', 'date'],
            'aadhaar_last4'    => ['nullable', 'digits:4'],
            'kyc_completed'    => ['nullable', 'boolean'],
            'status'           => ['required', 'in:active,inactive,suspended'],
            'is_active'        => ['nullable', 'boolean'],
            'is_blacklisted'   => ['nullable', 'boolean'],
            'internal_notes'   => ['nullable', 'string'],
            'tags'             => ['nullable', 'array'],
        ]);

        if ($request->user()) {
            $validated['updated_by'] = $request->user()->id;
        }

        $validated['land_unit'] = $validated['land_unit'] ?? 'acre';

        $customer->update($validated);

        return response()->json([
            'message' => "Customer [{$customer->name}] updated successfully.",
            'data'    => $customer->load('addresses.village'),
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

        if (!$customer->trashed()) {
            return response()->json([
                'message' => "Customer [{$customer->name}] is not deleted.",
                'data'    => $customer,
            ]);
        }

        $customer->restore();

        return response()->json([
            'message' => "Customer [{$customer->name}] restored successfully.",
            'data'    => $customer,
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
        $newState = !$customer->is_active;

        $customer->update([
            'is_active' => $newState,
            'status'    => $newState ? 'active' : 'inactive'
        ]);

        return response()->json([
            'message'   => "Customer account " . ($newState ? 'activated' : 'deactivated') . ".",
            'is_active' => $newState,
            'id'        => $customer->id,
        ]);
    }

    /**
     * Bulk actions on selected IDs.
     */
    public function bulkAction(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:activate,deactivate,delete,restore,force-delete'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:parties,id'],
        ]);

        $ids    = $validated['ids'];
        $action = $validated['action'];

        if ($action === 'restore') {
            abort_unless($request->user()?->can('customer-restore'), 403);
            Customer::withTrashed()->whereIn('id', $ids)->get()->each(function (Customer $customer): void {
                if ($customer->trashed()) {
                    $customer->restore();
                }
            });

            return response()->json([
                'message' => count($ids) . ' customer(s) restored successfully.',
                'ids'     => $ids,
            ]);
        }

        if ($action === 'delete') {
            abort_unless($request->user()?->can('customer-delete'), 403);
            Customer::whereIn('id', $ids)->delete();

            return response()->json([
                'message' => count($ids) . ' customer(s) deleted successfully.',
                'deleted' => $ids,
            ]);
        }

        if ($action === 'force-delete') {
            abort_unless($request->user()?->can('customer-permanent-delete'), 403);
            Customer::withTrashed()->whereIn('id', $ids)->get()->each(function (Customer $customer): void {
                $customer->forceDelete();
            });

            return response()->json([
                'message' => count($ids) . ' customer(s) permanently deleted successfully.',
                'deleted' => $ids,
            ]);
        }

        $isActive = $action === 'activate';

        Customer::whereIn('id', $ids)->update([
            'is_active' => $isActive,
            'status'    => $isActive ? 'active' : 'inactive'
        ]);

        return response()->json([
            'message'   => count($ids) . ' customer(s) ' . ($isActive ? 'activated' : 'deactivated') . ' successfully.',
            'is_active' => $isActive,
            'ids'       => $ids,
        ]);
    }

    /**
     * Place order from customer profile page.
     */
    public function placeOrder(Request $request, Customer $customer, OrderService $orderService)
    {
        try {
            $data = $request->validate([
                'order_id'              => [
                    'nullable',
                    \Illuminate\Validation\Rule::exists('orders', 'id')->where('party_id', $customer->id)
                ],
                'cart'                  => 'required|string',
                'applied_offer_id'      => 'nullable|exists:offers,id',
                'order_discount_amount' => 'nullable|numeric',
                'coupon_code'           => 'nullable|string',
                'coupon_discount'       => 'nullable|numeric',
                'tax_amount'            => 'required|numeric',
                'subtotal'              => 'required|numeric',
                'grand_total'           => 'required|numeric',
                'warehouse_id'          => 'required|exists:warehouses,id',
                'address_id'            => [
                    'required',
                    \Illuminate\Validation\Rule::exists('party_addresses', 'id')->where('party_id', $customer->id),
                ],
                'billing_address_id'    => [
                    'nullable',
                    \Illuminate\Validation\Rule::exists('party_addresses', 'id')->where('party_id', $customer->id),
                ],
                'is_draft'              => 'nullable|boolean',
                'future_order_date'     => 'required_if:is_draft,1|nullable|date_format:Y-m-d',
            ]);

            // Map Customer to Party
            $party = \App\Modules\Customers\Models\Party::findOrFail($customer->id);

            if (!empty($data['order_id'])) {
                $order = Order::where('party_id', $customer->id)->findOrFail($data['order_id']);
                $orderService->updateCustomerOrder($order, $data);
                $msg = 'Order updated successfully!';
            } else {
                $order = $orderService->placeCustomerOrder($party, $data);
                $msg   = 'Order placed successfully!';
            }

            return redirect()->route('customers.show', $customer)
                ->with('success', $msg)
                ->with('active_tab', 'history');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $firstError = collect($e->errors())->flatten()->first() ?? $e->getMessage();
            return back()->with('error', $firstError);
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to process order: ' . $e->getMessage());
        }
    }
}
