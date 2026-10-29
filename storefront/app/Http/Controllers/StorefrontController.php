<?php

namespace Storefront\Http\Controllers;

use App\Models\LeadSource;
use App\Modules\Catalog\Models\Category;
use App\Modules\Core\Models\Village;
use App\Modules\Customers\Models\Customer;
use App\Modules\Orders\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Storefront\Models\CustomerAccount;
use Storefront\Models\Product;
use Storefront\Notifications\VerifyStorefrontEmail;
use Storefront\Services\Checkout;
use Storefront\Services\CustomerEmailLoginCodes;
use Storefront\Services\CustomerOrderActions;
use Storefront\Services\StorefrontAddresses;

class StorefrontController extends Controller
{
    private function products()
    {
        return Product::storefrontAvailable()->with('taxRate');
    }

    private function account(): CustomerAccount
    {
        $account = Auth::guard('customer')->user();
        abort_unless($account && CustomerAccount::storefrontEligible()->whereKey($account->id)->exists(), 403, 'Your shopping account is unavailable. Please contact your branch.');

        return $account;
    }

    public function index(Request $request)
    {
        $request->validate(['q' => 'nullable|string|max:100', 'category' => 'nullable|integer', 'sort' => 'nullable|in:newest,price-low,price-high']);
        $products = $this->products()->when($request->filled('q'), fn ($q) => $q->where('name', 'like', '%'.$request->string('q').'%'))
            ->when($request->filled('category'), fn ($q) => $q->where('category_id', $request->integer('category')));
        match ($request->input('sort')) {
            'price-low' => $products->orderBy('selling_price')->orderBy('id'),
            'price-high' => $products->orderByDesc('selling_price')->orderBy('id'),
            default => $products->orderByDesc('is_featured')->orderByDesc('id'),
        };

        return view('shop.index', ['products' => $products->paginate(24)->withQueryString(), 'categories' => Category::where('is_active', true)->orderBy('name')->get()]);
    }

    public function product(string $slug)
    {
        return view('shop.product', ['product' => $this->products()->where('slug', $slug)->firstOrFail()]);
    }

    public function image(int $id, int $image)
    {
        $product = $this->products()->findOrFail($id);
        abort_unless($image === 1 && $product->image_path, 404);
        $base = realpath(dirname(base_path()).'/storage/app/public');
        $path = $base ? realpath($base.'/'.ltrim($product->image_path, '/')) : false;
        abort_unless($path && str_starts_with($path, $base.DIRECTORY_SEPARATOR) && is_file($path), 404);
        abort_unless(in_array(mime_content_type($path), ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/avif'], true), 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=3600', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function registerForm()
    {
        return view('shop.register');
    }

    public function register(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge(['email' => mb_strtolower(trim($request->input('email')))]);
        }
        $data = $request->validate([
            'name' => 'required|string|max:100', 'email' => 'required|email|max:255|unique:customer_accounts,email',
            'phone' => 'required|regex:/^[0-9+ ()-]{10,20}$/',
            'password' => ['required', 'confirmed', 'max:72', Password::min(12)->letters()->numbers()],
        ]);
        $account = DB::transaction(function () use ($data) {
            // Registration origin is server-owned, distinct from sales channels.
            // Reuse retired references without changing an administrator's master settings.
            $website = LeadSource::firstOrCreate(['name' => 'Website'], ['is_active' => true]);
            // Never attach a public signup to an existing CRM record by email alone.
            $customer = Customer::create(['firstname' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'], 'category' => 'individual', 'source' => [$website->id], 'is_active' => true, 'is_blacklisted' => false, 'status' => 'active']);
            $account = new CustomerAccount;
            $account->forceFill(['customer_id' => $customer->id, 'email' => $data['email'], 'password' => $data['password'], 'login_enabled' => true, 'ordering_enabled' => true])->save();

            return $account;
        });
        $this->sendVerification($account);

        return redirect('/shop/login')->with('success', 'Account created. Check your email and verify your address before signing in.');
    }

    private function sendVerification(CustomerAccount $account): void
    {
        try {
            $account->notify(new VerifyStorefrontEmail);
        } catch (\Throwable $error) {
            report($error);
            throw ValidationException::withMessages(['email' => 'Your account exists, but we could not send the verification email. Please use Resend verification or contact your branch.']);
        }
    }

    public function resendVerification(Request $request)
    {
        $data = $request->validate(['email' => 'required|email|max:255']);
        $account = CustomerAccount::where('email', mb_strtolower(trim($data['email'])))->whereNull('email_verified_at')->where('login_enabled', true)->first();
        if ($account) {
            $this->sendVerification($account);
        }

        return back()->with('success', 'If an unverified account exists, a new verification email has been sent.');
    }

    public function verifyEmail(int $id, string $hash)
    {
        $account = CustomerAccount::findOrFail($id);
        abort_unless(hash_equals(sha1($account->email), $hash), 403);
        if (! $account->email_verified_at) {
            $account->forceFill(['email_verified_at' => now()])->save();
        }

        return redirect('/shop/login')->with('success', 'Email verified. You can now sign in to your customer account.');
    }

    private function loginDestination(Request $request): string
    {
        $destination = $request->input('return_to', $request->session()->get('shop.login_return_to', 'shop'));

        // Keep customer navigation separate from staff auth's intended URL.
        return is_string($destination) && in_array($destination, ['shop', 'cart', 'orders', 'profile'], true) ? $destination : 'shop';
    }

    private function afterLogin(string $destination)
    {
        return redirect()->route(match ($destination) {
            'cart' => 'shop.cart',
            'orders' => 'shop.orders',
            'profile' => 'shop.profile',
            default => 'shop.index',
        });
    }

    public function loginForm(Request $request)
    {
        $returnTo = $this->loginDestination($request);
        if (Auth::guard('customer')->check()) {
            $request->session()->forget('shop.login_return_to');

            return $this->afterLogin($returnTo);
        }
        $request->session()->put('shop.login_return_to', $returnTo);
        $loginMethod = $request->query('method', $request->old('login_method', 'password')) === 'code' ? 'code' : 'password';

        return response()->view('shop.login', compact('returnTo', 'loginMethod'))->header('Cache-Control', 'no-store');
    }

    public function login(Request $request, CustomerEmailLoginCodes $codes)
    {
        $returnTo = $this->loginDestination($request);
        $request->session()->put('shop.login_return_to', $returnTo);
        $method = $request->input('login_method', 'password');
        $data = $request->validate(['email' => 'required|email|max:255', 'login_method' => 'sometimes|string|in:password,code', 'password' => $method === 'code' ? ['exclude'] : ['required', 'string', 'max:255']]);
        if ($method === 'code') {
            $email = mb_strtolower(trim($data['email']));
            $account = CustomerAccount::storefrontEligible()->where('email', $email)->first();
            $codes->start($request, $email, $account, $returnTo);

            return redirect()->route('shop.login.code');
        }
        if (! Auth::guard('customer')->attempt(['email' => $data['email'], 'password' => $data['password']])) {
            throw ValidationException::withMessages(['email' => 'Unable to sign in. Check your credentials or contact your branch to activate your customer account.']);
        }
        $codes->clear($request);
        $request->session()->regenerate();
        $request->session()->forget('shop.login_return_to');

        return $this->afterLogin($returnTo);
    }

    public function codeForm(Request $request, CustomerEmailLoginCodes $codes)
    {
        if (Auth::guard('customer')->check()) {
            return $this->afterLogin($this->loginDestination($request));
        }
        try {
            $details = $codes->details($request);
        } catch (ValidationException $error) {
            return redirect()->route('shop.login', ['method' => 'code'])->withErrors($error->errors());
        }

        return response()->view('shop.login-code', $details)->header('Cache-Control', 'no-store');
    }

    public function verifyLoginCode(Request $request, CustomerEmailLoginCodes $codes)
    {
        try {
            $data = Validator::make($request->only('code'), ['code' => 'required|digits:6'])->validate();
            $destination = $codes->verify($request, (string) $data['code']);
        } catch (ValidationException $error) {
            // Never flash a sign-in code into session old-input data.
            return $request->session()->has(CustomerEmailLoginCodes::SESSION_KEY)
                ? redirect()->route('shop.login.code')->withErrors($error->errors())
                : redirect()->route('shop.login', ['method' => 'code'])->withErrors($error->errors());
        }

        return $this->afterLogin($destination);
    }

    public function resendLoginCode(Request $request, CustomerEmailLoginCodes $codes)
    {
        $codes->resend($request);

        return redirect()->route('shop.login.code')->with('success', 'If your account is eligible, a new sign-in code has been sent.');
    }

    public function cancelLoginCode(Request $request, CustomerEmailLoginCodes $codes)
    {
        $codes->clear($request);

        return redirect()->route('shop.login', ['method' => 'code']);
    }

    public function logout(Request $request, CustomerEmailLoginCodes $codes)
    {
        $codes->clear($request);
        Auth::guard('customer')->logout();
        $request->session()->forget('shop.login_return_to');
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect('/shop');
    }

    private function lines(Request $request): array
    {
        $cart = $request->session()->get('shop.cart', []);
        if (! $cart) {
            return [];
        }
        $products = $this->products()->whereIn('id', array_keys($cart))->get();
        $lines = [];
        $available = [];
        foreach ($products as $product) {
            $rawQuantity = $cart[$product->id];
            if (! is_int($rawQuantity) && ! (is_string($rawQuantity) && ctype_digit($rawQuantity) && strlen($rawQuantity) <= 4)) {
                continue;
            }
            $quantity = (int) $rawQuantity;
            if ($quantity < max(1, $product->min_order_qty) || $quantity > min(1000, $product->max_order_qty ?? 1000)) {
                continue;
            }
            $available[$product->id] = $quantity;
            $lines[] = ['product' => $product, 'quantity' => $quantity, 'price' => (int) round($product->display_price * 100)];
        }
        if ($available !== $cart) {
            $request->session()->put('shop.cart', $available);
            if ($available != $cart) {
                $request->session()->put('shop.checkout_key', (string) Str::uuid());
                $request->session()->flash('cart_notice', 'Your bag changed: unavailable products or quantities outside updated order limits were removed. Please review your bag.');
            }
        }

        return $lines;
    }

    public function cart(Request $request)
    {
        $request->session()->put('shop.checkout_key', $request->session()->get('shop.checkout_key', (string) Str::uuid()));
        $lines = $this->lines($request);

        $quote = app(Checkout::class)->quote($request->session()->get('shop.cart', []));
        $request->session()->put('shop.quote', $quote ? hash('sha256', json_encode([$quote['items'], $quote['grand_total']])) : '');

        return view('shop.cart', ['lines' => $lines, 'total' => $quote ? (int) round($quote['grand_total'] * 100) : 0, 'quote' => $quote, 'summary' => app(Checkout::class)->summary($quote)]);
    }

    public function addressVillages(Request $request)
    {
        $this->account();
        $data = $request->validate(['search' => 'nullable|string|max:100']);
        $search = trim($data['search'] ?? '');
        if (mb_strlen($search) < 3) {
            return response()->json(['villages' => []]);
        }
        $villages = Village::query()->when($search !== '', function ($query) use ($search) {
            $query->where(function ($query) use ($search) {
                foreach (['village_name', 'post_so_name', 'taluka_name', 'district_name', 'state_name', 'pincode'] as $field) {
                    $query->orWhere($field, 'like', '%'.$search.'%');
                }
            });
        })->orderBy('village_name')->limit(50)
            ->get(['id', 'village_name', 'post_so_name', 'taluka_name', 'district_name', 'state_name', 'pincode']);

        return response()->json(['villages' => $villages]);
    }

    public function storeAddress(Request $request, StorefrontAddresses $addresses, ?int $addressId = null)
    {
        $account = $this->account();
        $rules = ['label' => 'required|string|max:255', 'address_line_1' => 'required|string|max:255',
            'village_name' => 'required_without:city|nullable|string|max:255', 'city' => 'nullable|string|max:255',
            'state' => 'required|string|max:255', 'pincode' => 'required|regex:/^[0-9]{6}$/',
            'village_id' => ['nullable', 'integer', Rule::exists('villages', 'id')->whereNull('deleted_at')],
            'is_default' => 'sometimes|boolean', 'customer_id' => 'prohibited', 'id' => 'prohibited', 'status' => 'prohibited'];
        foreach (['address_line_2', 'post_office', 'taluka', 'district'] as $field) {
            $rules[$field] = 'nullable|string|max:255';
        }
        $address = $addresses->save($account, $request->validate($rules), $addressId);
        if (! $request->expectsJson()) {
            return redirect()->route('shop.cart')->with('success', 'Delivery address saved.');
        }

        $available = $addresses->available($account->customer);

        return response()->json(['address' => $addresses->data($address), 'html' => view('shop.saved-addresses', [
            'addresses' => $available, 'selectedId' => $address->id,
        ])->render(), 'book_html' => view('shop.address-book', ['addresses' => $available])->render(), 'billing_html' => view('shop.billing-addresses', ['addresses' => $available])->render()], 201);
    }

    public function updateCart(Request $request)
    {
        $data = $request->validate(['product_id' => 'required|integer', 'quantity' => 'required|integer|min:0|max:1000']);
        $cart = $request->session()->get('shop.cart', []);
        if ((int) $data['quantity'] === 0) {
            unset($cart[$data['product_id']]);
        } else {
            $product = $this->products()->findOrFail($data['product_id']);
            if ($data['quantity'] < max(1, $product->min_order_qty) || $data['quantity'] > ($product->max_order_qty ?? 1000)) {
                throw ValidationException::withMessages(['quantity' => 'Choose a quantity within this product’s order limits.']);
            }
            abort_if(! isset($cart[$product->id]) && count($cart) >= 50, 422, 'Your cart can contain up to 50 products.');
            $cart[$product->id] = (int) $data['quantity'];
        }
        $request->session()->put('shop.cart', $cart);
        $request->session()->put('shop.checkout_key', (string) Str::uuid());

        if ($request->expectsJson()) {
            $lines = $this->lines($request);
            $cart = $request->session()->get('shop.cart', []);
            $quote = app(Checkout::class)->quote($cart);
            $request->session()->put('shop.quote', $quote ? hash('sha256', json_encode([$quote['items'], $quote['grand_total']])) : '');

            return response()->json([
                'quantity' => (int) ($cart[$data['product_id']] ?? 0),
                'count' => array_sum($cart),
                'product_count' => count($lines),
                'total' => $quote ? (int) round($quote['grand_total'] * 100) : 0,
                'line_total' => collect($lines)->where('product.id', (int) $data['product_id'])->sum(fn ($line) => $line['price'] * $line['quantity']),
                'summary' => app(Checkout::class)->summary($quote),
                'checkout_key' => $request->session()->get('shop.checkout_key'),
            ]);
        }

        return redirect('/shop/cart')->with('success', 'Your cart has been updated.');
    }

    public function checkout(Request $request, Checkout $checkout)
    {
        if (! Auth::guard('customer')->check()) {
            $request->session()->put('shop.login_return_to', 'cart');

            return redirect()->route('shop.login');
        }
        $checkout->place($request, $this->account());
        if (hash_equals((string) $request->session()->get('shop.checkout_key'), (string) $request->input('checkout_key'))) {
            $request->session()->forget(['shop.cart', 'shop.checkout_key', 'shop.quote']);
        }

        return redirect()->route('shop.orders')->with('success', 'Order placed! Payment is due on delivery.');
    }

    public function profile(Request $request)
    {
        if (! Auth::guard('customer')->check()) {
            $request->session()->put('shop.login_return_to', 'profile');

            return redirect('/shop/login');
        }

        $account = $this->account()->load('customer');

        return view('shop.profile', compact('account'));
    }

    public function updateProfile(Request $request)
    {
        if (! Auth::guard('customer')->check()) {
            $request->session()->put('shop.login_return_to', 'profile');

            return redirect('/shop/login');
        }

        $account = $this->account();
        // Only personal contact fields may be changed; linked identity and access stay server-owned.
        $data = $request->validate([
            'firstname' => 'required|string|max:100',
            'middlename' => 'nullable|string|max:100',
            'lastname' => 'nullable|string|max:100',
            'phone' => 'required|regex:/^[0-9+ ()-]{10,20}$/',
        ]);

        DB::transaction(function () use ($account, $data) {
            $customer = Customer::whereKey($account->customer_id)->lockForUpdate()->firstOrFail();
            $lockedAccount = CustomerAccount::whereKey($account->id)->lockForUpdate()->firstOrFail();
            abort_unless($lockedAccount->customer_id === $customer->id && $lockedAccount->login_enabled
                && $lockedAccount->email_verified_at && $customer->is_active && ! $customer->is_blacklisted, 403);
            $customer->fill($data)->save();
        });

        return redirect()->route('shop.profile')->with('success', 'Your profile details have been updated.');
    }

    public function orders(Request $request)
    {
        if (! Auth::guard('customer')->check()) {
            $request->session()->put('shop.login_return_to', 'orders');

            return redirect('/shop/login');
        }
        $account = $this->account();
        $orders = Order::where('party_id', $account->customer_id)->with(['payments', 'creator:id,name'])->orderByDesc('id')->paginate(10);
        $items = DB::table('order_items')->leftJoin('products', 'products.id', '=', 'order_items.product_id')->whereIn('order_id', $orders->pluck('id'))->select('order_items.*', 'products.name')->get()->groupBy('order_id');
        $logs = DB::table('order_status_logs')->whereIn('order_id', $orders->pluck('id'))->orderBy('id')->get()->groupBy('order_id');

        $customerPlacedIds = DB::table('storefront_checkouts')->where('customer_account_id', $account->id)
            ->whereIn('order_id', $orders->pluck('id'))->pluck('order_id')->map(fn ($id) => (int) $id)->all();
        $placedBy = $orders->getCollection()->mapWithKeys(fn ($order) => [
            $order->id => in_array((int) $order->id, $customerPlacedIds, true)
                ? ($account->customer?->name ?: 'Customer')
                : ($order->creator?->name ?: 'Not recorded'),
        ]);

        return view('shop.orders', compact('orders', 'items', 'logs', 'placedBy'));
    }

    public function orderAction(Request $request, int $id, CustomerOrderActions $service)
    {
        $account = $this->account();
        $data = $request->validate(['status' => 'required|in:cancelled,return_requested', 'expected_status' => 'required|string|max:50', 'notes' => 'required|string|max:1000']);
        $service->apply($account, $id, $data);

        return redirect()->route('shop.orders')->with('success', 'Order updated.');
    }
}
