<?php

// Run: php tests/regression/query_performance.php
// All fixture writes go to a unique temporary database containing schema copies
// and synthetic data. Existing application records are never copied or modified.

declare(strict_types=1);

use App\Modules\Core\Controllers\PageController;
use App\Modules\Core\Controllers\VillageController;
use App\Modules\Customers\Controllers\CustomerController;
use App\Modules\Inventory\Controllers\StockTransferController;
use App\Modules\Inventory\Models\StockTransfer;
use App\Modules\Inventory\Services\WarehouseAnalyticsService;
use App\Modules\Orders\Controllers\InvoiceController;
use App\Modules\Orders\Controllers\PaymentController;
use App\Modules\Orders\Controllers\PromotionsController;
use App\Modules\Orders\Controllers\RefundController;
use App\Modules\Orders\Models\Invoice;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Refund;
use App\Modules\Users\Controllers\UserController;
use App\Modules\Users\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

class QueryPerformanceViewer extends User
{
    public ?string $reviewState = null;

    public function can($abilities, $arguments = [])
    {
        return true;
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        return true;
    }

    public function getLobTeamIdAttribute(): ?int
    {
        return null;
    }

    public function getLobStateNameAttribute(): ?string
    {
        return $this->reviewState;
    }
}

class RestrictedQueryPerformanceViewer extends QueryPerformanceViewer
{
    public function can($abilities, $arguments = [])
    {
        return in_array($abilities, ['user-view', 'customer-view'], true);
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        return false;
    }

    public function getLobTeamIdAttribute(): ?int
    {
        return 1;
    }
}

function verifyQuery(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

function queryResponse(string $class, string $query = '', string $method = 'index'): array
{
    global $app, $viewer;
    $request = Request::create('http://localhost/api/query-regression?'.$query);
    $request->headers->set('Accept', 'application/json');
    $request->setUserResolver(fn () => $viewer);
    $app->instance('request', $request);
    DB::flushQueryLog();
    DB::enableQueryLog();
    try {
        $data = app($class)->{$method}($request)->getData(true);
        $count = count(DB::getQueryLog());
    } finally {
        DB::disableQueryLog();
    }

    return [$data, $count];
}

$source = DB::connection();
verifyQuery($source->getDriverName() === 'mysql', 'MySQL is required.');
$adminPdo = $source->getPdo();
$sourceDatabase = str_replace('`', '``', $source->getDatabaseName());
$fixtureDatabase = 'metis_query_test_'.bin2hex(random_bytes(6));
$config = $source->getConfig();
$config['database'] = $fixtureDatabase;
unset($config['url'], $config['name']);
$adminPdo->exec("CREATE DATABASE `{$fixtureDatabase}`");
$failed = false;

try {
    $tables = [
        'users', 'departments', 'designations', 'employment_types', 'roles',
        'permissions', 'model_has_roles', 'model_has_permissions', 'role_has_permissions',
        'teams', 'login_histories', 'sessions', 'personal_access_tokens', 'parties',
        'party_addresses', 'villages', 'orders', 'invoices', 'payments', 'refunds',
        'warehouses', 'products', 'stocks', 'stock_transfers', 'order_returns',
        'purchase_orders', 'coupons', 'shipments', 'order_items', 'services',
        'service_provider_users', 'chat_presence', 'stock_movements', 'goods_receipts',
        'inventory_adjustments', 'village_service_mappings', 'stock_transfer_items',
        'inventory_adjustment_items', 'order_return_items', 'attendances',
        'order_status_logs', 'product_attributes', 'product_attribute_values', 'categories', 'brands', 'activity_log', 'audits', 'suppliers', 'order_complaints', 'order_complaint_status_logs', 'order_complaint_replies', 'credit_notes', 'offers', 'call_tags', 'call_tag_form_fields', 'tax_rates', 'purchase_order_items', 'goods_receipt_items',
    ];
    foreach ($tables as $table) {
        $adminPdo->exec("CREATE TABLE `{$fixtureDatabase}`.`{$table}` LIKE `{$sourceDatabase}`.`{$table}`");
    }
    config([
        'database.connections.mysql' => $config, 'database.default' => 'mysql',
        'cache.default' => 'array', 'permission.cache.store' => 'array',
    ]);
    DB::purge('mysql');
    Schema::clearResolvedInstance('db.schema');
    verifyQuery(DB::connection()->getDatabaseName() === $fixtureDatabase, 'Test connection is not isolated.');
    app(PermissionRegistrar::class)->initializeCache();
    Carbon::setTestNow('2026-10-16 12:00:00');
    $now = '2026-10-16 12:00:00';
    DB::table('teams')->insert(['id' => 1, 'name' => 'Gujarat', 'code' => 'GJ', 'state_name' => 'Gujarat']);
    DB::table('roles')->insert(['id' => 1, 'name' => 'Sales', 'guard_name' => 'web', 'team_id' => 1]);
    DB::table('warehouses')->insert([['id' => 1, 'name' => 'One', 'code' => 'ONE'], ['id' => 2, 'name' => 'Two', 'code' => 'TWO']]);
    DB::table('products')->insert(['id' => 1, 'name' => 'Product', 'sku' => 'QTEST', 'slug' => 'qtest', 'min_stock_level' => 5]);
    DB::table('stocks')->insert(['product_id' => 1, 'warehouse_id' => 1, 'quantity' => 2]);
    for ($i = 1; $i <= 30; $i++) {
        DB::table('users')->insert(['id' => $i, 'name' => sprintf('User %02d', $i), 'email' => "test{$i}@example.invalid", 'password' => 'unused-test-hash', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('model_has_roles')->insert(['role_id' => 1, 'model_id' => $i, 'model_type' => User::class, 'team_id' => $i % 2 ? 1 : null]);
        DB::table('parties')->insert(['id' => $i, 'uuid' => Str::uuid()->toString(), 'party_code' => 'TEST-'.$i, 'type' => 'customer', 'firstname' => 'Customer', 'lastname' => (string) $i, 'phone' => sprintf('900000%04d', $i), 'created_by' => 1, 'status' => $i % 2 ? 'active' : 'inactive', 'is_blacklisted' => $i % 3 === 0, 'kyc_completed' => $i % 4 === 0, 'created_at' => $now, 'updated_at' => $now]);
        DB::table('party_addresses')->insert(['party_id' => $i, 'state' => $i % 2 ? 'Gujarat' : 'Rajasthan', 'is_default' => true]);
        DB::table('sessions')->insert(['id' => 'test-session-'.$i, 'user_id' => $i, 'payload' => '', 'last_activity' => strtotime($i % 2 ? $now : '2026-10-01 12:00:00'), 'user_agent' => 'Desktop']);
        for ($j = 1; $j <= 3; $j++) {
            $status = ['pending', 'delivered', 'cancelled'][$j - 1];
            $date = $j === 3 ? '2026-09-01 12:00:00' : $now;
            $o = DB::table('orders')->insertGetId(['order_no' => "TEST-$i-$j", 'type' => 'sale', 'party_id' => $i, 'order_date' => $date, 'status' => $status, 'warehouse_id' => $i % 2 ? 1 : 2, 'net_amount' => 100, 'total_amount' => 100, 'created_by' => $i, 'created_at' => $date, 'updated_at' => $date]);
            $invoice = DB::table('invoices')->insertGetId(['invoice_no' => "INV-$i-$j", 'order_id' => $o, 'invoice_date' => $date, 'total_amount' => 100, 'tax_amount' => 0, 'net_amount' => 100, 'status' => 'partially_paid', 'created_at' => $date, 'updated_at' => $date]);
            DB::table('payments')->insert(['payment_no' => "PAY-$i-$j", 'invoice_id' => $invoice, 'order_id' => $o, 'amount' => $j === 3 ? 150 : 25, 'status' => 'completed', 'payment_date' => $date, 'created_at' => $date, 'updated_at' => $date]);
            DB::table('refunds')->insert(['refund_no' => "REF-$i-$j", 'invoice_id' => $invoice, 'order_id' => $o, 'amount' => 5, 'status' => 'completed', 'created_at' => $date, 'updated_at' => $date]);
            DB::table('shipments')->insert(['shipment_no' => "SHIP-$i-$j", 'order_id' => $o, 'status' => 'pending', 'created_at' => $date, 'updated_at' => $date]);
        }
    }
    for ($i = 1; $i <= 10; $i++) {
        DB::table('coupons')->insert(['code' => 'COUPON-'.$i, 'type' => 'fixed', 'value' => 10, 'is_active' => $i % 2, 'expiry_date' => $i % 2 ? '2026-10-17' : '2026-10-01']);
    }
    DB::table('stock_movements')->insert(['product_id' => 1, 'warehouse_id' => 1, 'quantity' => 5, 'type' => 'transfer', 'reference_type' => StockTransfer::class, 'reference_id' => 1, 'created_at' => $now, 'updated_at' => $now]);
    DB::table('stock_transfers')->insert(['transfer_no' => 'TRANSFER-1', 'from_warehouse_id' => 1, 'to_warehouse_id' => 2, 'status' => 'draft', 'created_at' => '2026-09-01 12:00:00', 'updated_at' => '2026-09-01 12:00:00']);
    for ($s = 1; $s <= 5; $s++) {
        DB::table('services')->updateOrInsert(['id' => $s], ['name' => 'Service '.$s, 'code' => 'S'.$s]);
    }
    for ($i = 1; $i <= 20; $i++) {
        DB::table('villages')->updateOrInsert(['id' => $i], ['village_name' => 'Village '.$i, 'normalized_name' => 'village '.$i, 'pincode' => '380001', 'state_name' => $i % 2 ? 'Gujarat' : 'Rajasthan', 'district_name' => 'District']);
        foreach ([1, 2, 3, 4] as $s) {
            DB::table('village_service_mappings')->updateOrInsert(['village_id' => $i, 'service_id' => $s], ['is_available' => ($i + $s) % 2]);
        }
    }

    DB::table('orders')->where('id', 1)->update(['status' => 'dispatched']);
    DB::table('shipments')->where('order_id', 1)->update(['delivery_attempts' => 1]);
    DB::table('order_returns')->updateOrInsert(['return_no' => 'RET-1'], ['order_id' => 2, 'status' => 'completed', 'refund_amount' => 10, 'created_at' => '2026-10-16 12:00:00']);
    DB::table('order_returns')->updateOrInsert(['return_no' => 'RET-2'], ['order_id' => 2, 'status' => 'rejected', 'refund_amount' => 5, 'created_at' => '2026-10-16 12:00:00']);
    DB::table('order_returns')->updateOrInsert(['return_no' => 'RET-3'], ['order_id' => 3, 'status' => 'requested', 'refund_amount' => 7, 'created_at' => '2026-10-16 12:00:00']);
    DB::table('payments')->where('invoice_id', 90)->update(['deleted_at' => '2026-10-16 12:00:00']);
    DB::table('invoices')->where('id', 89)->update(['deleted_at' => '2026-10-16 12:00:00']);
    DB::table('refunds')->where('invoice_id', 88)->delete();
    foreach (['2026-09-30 23:59:59', '2026-10-01 00:00:00', '2026-10-31 23:59:59', '2026-11-01 00:00:00'] as $n => $date) {
        DB::table('shipments')->updateOrInsert(['shipment_no' => 'BOUNDARY-'.$n], ['order_id' => 1, 'created_at' => $date, 'delivery_attempts' => 1]);
        DB::table('attendances')->updateOrInsert(['user_id' => 1, 'date' => substr($date, 0, 10)], ['status' => 'Present']);
    }

    DB::table('product_attributes')->insert(['id' => 1, 'name' => 'Size']);
    DB::table('product_attribute_values')->insert(['id' => 1, 'product_attribute_id' => 1, 'value' => 'Large']);
    for ($i = 1; $i <= 90; $i++) {
        DB::table('order_items')->insert(['order_id' => $i, 'product_id' => 1, 'product_variant_id' => 1, 'quantity' => 2, 'unit_price' => 50, 'total_amount' => 100]);
    }

    $viewer = new QueryPerformanceViewer;
    $viewer->setRawAttributes(User::findOrFail(1)->getAttributes(), true);
    auth()->setUser($viewer);

    [$dashboard, $queries] = queryResponse(PageController::class, 'filter=this_month', 'dashboard');
    verifyQuery($queries <= 18, 'Dashboard query count grows with orders or item variants.');
    verifyQuery(count($dashboard['dashboardData']['recentOrders']) === 60, 'Dashboard order list changed.');
    foreach ($dashboard['dashboardData']['recentOrders'] as $row) {
        verifyQuery($row['items'][0]['name'] === 'Product (Large)', 'Dashboard variant labels changed.');
    }
    echo "PASS: dashboard query budget, complete order list, and variant labels.\n";

    [$customers, $queries] = queryResponse(CustomerController::class, 'per_page=10');
    verifyQuery($queries <= 5, 'Customer query count regressed.');
    verifyQuery($customers['stats']['total'] === 30 && $customers['stats']['kyc_completed'] === 7, 'Customer aggregate casts changed.');
    foreach ($customers['data']['data'] as $customer) {
        $invoices = Invoice::with('payments')
            ->join('orders', 'invoices.order_id', '=', 'orders.id')
            ->where('orders.party_id', $customer['id'])
            ->whereIn('invoices.status', ['unpaid', 'partially_paid'])->select('invoices.*')->get();
        $expected = $invoices->sum(fn ($invoice) => $invoice->due_amount);
        verifyQuery(abs($customer['calculated_outstanding'] - $expected) < 0.001, 'Outstanding balances changed.');
    }
    $viewer->reviewState = 'Gujarat';
    [$scoped] = queryResponse(CustomerController::class);
    verifyQuery($scoped['stats']['total'] === 15, 'Customer state scope changed.');
    $viewer->reviewState = null;
    echo "PASS: customer query budget, balances, soft deletes, overpayments, and state scope.\n";

    [$invoices, $queries] = queryResponse(InvoiceController::class, 'limit=10&sort_field=id&sort_direction=asc');
    verifyQuery($queries <= 9, 'Invoice query count regressed.');
    verifyQuery($invoices['invoices']['data'][0]['order']['lifecycle_status'] === 'delivery_attempted', 'Shipment lifecycle changed.');
    verifyQuery($invoices['invoices']['data'][1]['order']['lifecycle_status'] === 'delivered', 'Latest rejected return changed lifecycle.');
    foreach ($invoices['invoices']['data'] as $invoice) {
        $expected = Refund::where('invoice_id', $invoice['id'])->where('status', 'completed')->sum('amount');
        verifyQuery($invoice['refunded_amount'] === $expected, 'Refund value or JSON type changed.');
        verifyQuery(! array_key_exists('refunded_amount_total', $invoice), 'Aggregate helper leaked into response.');
        verifyQuery(! array_key_exists('order_returns', $invoice['order']) && ! array_key_exists('shipments', $invoice['order']), 'Eager loading changed response shape.');
    }
    [$payments, $queries] = queryResponse(PaymentController::class, 'limit=10');
    verifyQuery($queries <= 10, 'Payment query count regressed.');
    verifyQuery($payments['stats']['total_volume'] === 5850.0 || $payments['stats']['total_volume'] === 5850, 'Payment aggregate includes deleted payments.');
    [$refunds, $queries] = queryResponse(RefundController::class, 'limit=5');
    verifyQuery($queries <= 10 && $refunds['stats']['total_refunded'] == 445, 'Refund aggregates regressed.');
    echo "PASS: invoice/payment/refund query budgets, lifecycle labels, totals, and response types.\n";

    [$users, $queries] = queryResponse(UserController::class, 'per_page=10');
    verifyQuery($queries <= 30 && $users['total'] === 30, 'User listing queries regressed.');
    foreach ($users['data'] as $user) {
        verifyQuery($user['is_online'] === true, 'Online-first ordering changed.');
    }
    [$team] = queryResponse(UserController::class, 'team_id=1');
    [$global] = queryResponse(UserController::class, 'team_id=global');
    verifyQuery($team['total'] === 15 && $global['total'] === 15, 'Team/global filtering changed.');
    [$empty] = queryResponse(UserController::class, 'team_id=999999');
    verifyQuery($empty['total'] === 0 && $empty['data'] === [], 'Empty user query changed.');
    DB::table('users')->where('id', $users['data'][0]['id'])->update(['is_active' => false]);
    [$inactiveWord] = queryResponse(UserController::class, 'is_active=false');
    [$inactiveNumber] = queryResponse(UserController::class, 'is_active=0');
    verifyQuery($inactiveWord['total'] > 0 && $inactiveWord['total'] === $inactiveNumber['total'], 'Boolean inactive filters must accept false and zero consistently.');
    echo "PASS: user activity ordering, team filters, and empty results.\n";

    [$coupons, $queries] = queryResponse(PromotionsController::class, '', 'couponsIndex');
    verifyQuery($queries <= 3 && $coupons['stats'] === ['total' => 10, 'active' => 5, 'inactive' => 5, 'expired' => 5, 'expiring_soon' => 5], 'Coupon aggregates changed.');
    [$villages, $queries] = queryResponse(VillageController::class, 'state=Gujarat');
    verifyQuery($queries <= 16, 'Village query count grows with services.');
    verifyQuery(array_column($villages['stats']['service_distribution'], 'count') === [0, 10, 0, 10, 0], 'Service counts changed.');
    [$transfers, $queries] = queryResponse(StockTransferController::class);
    verifyQuery($queries <= 5 && $transfers['stats'] === ['total' => 1, 'draft' => 1, 'pending' => 0, 'received' => 0], 'Transfer aggregate changed.');
    echo "PASS: coupon, village-service, and transfer aggregates.\n";

    $analytics = new WarehouseAnalyticsService;
    DB::flushQueryLog();
    DB::enableQueryLog();
    $fulfillment = $analytics->getFulfillmentPerformance(null, 'all');
    verifyQuery(count(DB::getQueryLog()) === 1, 'Fulfillment uses repeated scans.');
    DB::disableQueryLog();
    verifyQuery($fulfillment === ['total' => 90, 'delivered' => 30, 'cancelled' => 30, 'returned' => 0, 'fulfillment_rate' => 50.0], 'Fulfillment aggregates changed.');
    $pipeline = $analytics->getFulfillmentPipeline(null, 'all');
    verifyQuery($pipeline['returns_requested']['count'] === 1 && $pipeline['returns_requested']['amount'] === 7.0, 'Return request aggregate changed.');
    verifyQuery($analytics->getRecentActivity(null, 50, 'TRANSFER-1')->count() === 1, 'Full-class transfer search failed.');
    DB::table('stock_movements')->insert(['product_id' => 1, 'warehouse_id' => 1, 'quantity' => 1, 'type' => 'transfer', 'reference_type' => 'transfer', 'reference_id' => 1, 'created_at' => $now]);
    verifyQuery($analytics->getRecentActivity(null, 50, 'TRANSFER-1')->count() === 2, 'Mixed alias/full-class transfer search failed.');
    $chat = new ChatService;
    $chat->listUsersWithPresence($viewer, 'User 02');
    $other = $chat->listUsersWithPresence($viewer, 'User 03')->first();
    verifyQuery($other['today_orders'] === 2 && $other['today_revenue'] === 200.0, 'Chat cache lost totals for a different search.');
    echo "PASS: fulfillment query budget, return aggregation, mixed morph searches, and chat cache coverage.\n";

    $legacy = DB::table('shipments')->whereDate('created_at', '>=', '2026-10-01')->whereDate('created_at', '<=', '2026-10-31')->orderBy('id')->pluck('id')->all();
    $range = DB::table('shipments')->where('created_at', '>=', '2026-10-01 00:00:00')->where('created_at', '<', '2026-11-01 00:00:00')->orderBy('id')->pluck('id')->all();
    verifyQuery($legacy === $range, 'Date boundaries changed.');
    $legacy = DB::table('attendances')->whereYear('date', 2026)->whereMonth('date', 10)->orderBy('id')->pluck('id')->all();
    $range = DB::table('attendances')->where('date', '>=', '2026-10-01')->where('date', '<', '2026-11-01')->orderBy('id')->pluck('id')->all();
    verifyQuery($legacy === $range, 'Attendance month boundaries changed.');
    $plan = DB::select("EXPLAIN SELECT id FROM shipments WHERE created_at >= '2026-10-01' AND created_at < '2026-11-01'")[0];
    verifyQuery(str_contains($plan->possible_keys ?? '', 'created_at'), 'Date range cannot use the existing index.');
    echo "PASS: date/month equivalence and indexed date query plan.\n";
    // Check the optimized getters against their unchanged fallback queries.
    DB::table('payments')->insert(['payment_no' => 'PENDING-CHECK', 'invoice_id' => 1, 'order_id' => 1, 'amount' => 999, 'status' => 'pending', 'payment_date' => $now]);
    DB::table('refunds')->insert(['refund_no' => 'PENDING-CHECK', 'invoice_id' => 1, 'order_id' => 1, 'amount' => 999, 'status' => 'pending']);
    foreach ([1, 88, 90] as $id) {
        $plain = Invoice::findOrFail($id);
        $aggregated = Invoice::withSum(['refunds as refunded_amount_total' => fn ($query) => $query->where('status', 'completed')], 'amount')->findOrFail($id);
        verifyQuery($plain->refunded_amount === $aggregated->refunded_amount, 'Invoice getter fallback differs from SQL aggregate.');
        $plainOrder = Order::findOrFail($id);
        $aggregatedOrder = Order::withSum(['payments as total_paid_amount' => fn ($query) => $query->where('status', 'completed')], 'amount')->findOrFail($id);
        verifyQuery($plainOrder->total_paid === $aggregatedOrder->total_paid, 'Order getter fallback differs from SQL aggregate.');
    }
    echo "PASS: optimized financial getters match fallback queries, including pending and deleted transactions.\n";

    $restricted = new RestrictedQueryPerformanceViewer;
    $restricted->setRawAttributes(User::findOrFail(1)->getAttributes(), true);
    $restricted->reviewState = 'Gujarat';
    $viewer = $restricted;
    auth()->setUser($viewer);
    DB::table('parties')->where('id', 3)->update(['created_by' => 2]);
    [$restrictedCustomers] = queryResponse(CustomerController::class, 'per_page=100');
    verifyQuery($restrictedCustomers['stats']['total'] === 14, 'Restricted customer count includes another owner or state.');
    foreach ($restrictedCustomers['data']['data'] as $customer) {
        verifyQuery($customer['created_by'] === 1 && $customer['id'] !== 3, 'Customer owner scope leaked.');
        verifyQuery(count(array_filter($customer['addresses'], fn ($address) => $address['is_default'] && $address['state'] === 'Gujarat')) > 0, 'Customer state scope leaked.');
    }
    [$restrictedUsers] = queryResponse(UserController::class, 'per_page=100');
    verifyQuery($restrictedUsers['total'] === 15, 'Restricted user team scope changed.');
    foreach ($restrictedUsers['data'] as $user) {
        verifyQuery($user['id'] % 2 === 1, 'User from another team leaked.');
    }
    [$intersection] = queryResponse(UserController::class, 'team_id=global');
    verifyQuery($intersection['total'] === 0, 'Global-team filter bypassed the viewer team scope.');
    echo "PASS: restricted customer owner/state scopes and user team intersections.\n";
    $ledger = new ReflectionMethod(PageController::class, 'exportStockLedger');
    ob_start();
    try {
        $ledger->invoke(new PageController, '2026-10-16', '2026-10-16')->sendContent();
        $csv = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    $ledgerRows = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));
    verifyQuery(count($ledgerRows) === 3, 'Stock-ledger date filtering changed.');
    verifyQuery($ledgerRows[0] === ['Date', 'Type', 'SKU', 'Product Name', 'Warehouse', 'Qty Before', 'Qty Moved', 'Qty After'], 'Stock-ledger CSV header changed.');
    foreach (array_slice($ledgerRows, 1) as $row) {
        verifyQuery(count($row) === 8 && $row[5] === '' && $row[7] === '', 'Stock-ledger CSV shape changed or balances were fabricated.');
    }
    echo "PASS: original eight-column stock-ledger CSV format and date filters.\n";
    // Exports must preserve row values without issuing a query per product.
    for ($i = 2; $i <= 12; $i++) {
        DB::table('products')->insert(['id' => $i, 'name' => 'Export '.$i, 'sku' => 'EXPORT-'.$i, 'slug' => 'export-'.$i]);
        DB::table('order_items')->insert(['order_id' => 1, 'product_id' => $i, 'quantity' => 2, 'unit_price' => 5, 'total_amount' => 10]);
        DB::table('stocks')->insert([['product_id' => $i, 'warehouse_id' => 1, 'quantity' => 3], ['product_id' => $i, 'warehouse_id' => 2, 'quantity' => 4]]);
    }
    DB::table('products')->insert(['id' => 13, 'name' => 'No stock', 'sku' => 'NO-STOCK', 'slug' => 'no-stock']);
    DB::table('order_items')->insert(['order_id' => 1, 'product_id' => 13, 'quantity' => 1, 'unit_price' => 5, 'total_amount' => 5]);
    DB::flushQueryLog();
    DB::enableQueryLog();
    ob_start();
    try {
        (new ReflectionMethod(PageController::class, 'exportProductSales'))->invoke(new PageController, null, null)->sendContent();
        $productCsv = ob_get_contents();
    } finally {
        ob_end_clean();
        DB::disableQueryLog();
    }
    verifyQuery(count(DB::getQueryLog()) === 1, 'Product export query count grows with products.');
    $productRows = array_map('str_getcsv', array_filter(explode("\n", trim($productCsv))));
    verifyQuery(count($productRows) === 14, 'Product export omitted a sold product.');
    foreach (array_slice($productRows, 1) as $row) {
        if ($row[1] === 'NO-STOCK') { verifyQuery((float) $row[7] === 0.0, 'Missing stock must remain zero.'); }
        if (str_starts_with($row[1], 'EXPORT-')) {
            verifyQuery((float) $row[4] === 2.0 && (float) $row[6] === 10.0 && (float) $row[7] === 7.0, 'Stock join multiplied sales or changed inventory totals.');
        }
    }
    set_error_handler(static function ($severity, $message, $file, $line) { throw new ErrorException($message, 0, $severity, $file, $line); });
    ob_start();
    try {
        (new ReflectionMethod(PageController::class, 'exportSalesRegion'))->invoke(new PageController, '2040-01-01', '2040-01-31')->sendContent();
        $regionCsv = ob_get_contents();
        ob_clean();
        (new ReflectionMethod(PageController::class, 'exportAuditTrail'))->invoke(new PageController, '2040-01-01', '2040-01-31')->sendContent();
        $auditCsv = ob_get_contents();
    } finally {
        ob_end_clean();
        restore_error_handler();
    }
    verifyQuery(count(array_filter(explode("\n", trim($regionCsv)))) === 1, 'Regional export ignored date boundaries.');
    verifyQuery(count(array_filter(explode("\n", trim($auditCsv)))) === 1, 'Audit export ignored date boundaries.');
    echo "PASS: single-query product export, multiple stock rows, and regional/audit export date closures.\n";

    [$emptyAudit, $emptyAuditQueries] = queryResponse(\App\Http\Controllers\AuditLogController::class);
    verifyQuery($emptyAudit['stats'] === ['total' => 0, 'created' => 0, 'updated' => 0, 'deleted' => 0], 'Empty audit statistics must be integer zeros.');
    foreach (['created', 'created', 'updated', 'deleted', 'restored'] as $event) {
        DB::table('audits')->insert(['event' => $event, 'auditable_type' => User::class, 'auditable_id' => 1, 'old_values' => '{}', 'new_values' => '{}', 'created_at' => $now, 'updated_at' => $now]);
    }
    [$auditStats] = queryResponse(\App\Http\Controllers\AuditLogController::class, 'event=created');
    verifyQuery($auditStats['stats'] === ['total' => 5, 'created' => 2, 'updated' => 1, 'deleted' => 1] && $auditStats['total'] === 2, 'Audit global statistics or filtered rows changed.');
    verifyQuery($emptyAuditQueries === 2, 'Empty audit list must use one pagination count and one statistics query.');
    echo "PASS: audit statistics in one scan, integer zeros, extra events, and filtered list semantics.\n";

    require __DIR__.'/performance_followup.php';

    if (getenv('METIS_K6_ISOLATED') === '1') {
        require dirname(__DIR__).'/performance/isolated-http.php';
    }
} catch (Throwable $e) {
    $failed = true;
    fwrite(STDERR, 'FAIL: '.$e->getMessage().PHP_EOL);
} finally {
    DB::disconnect('mysql');
    $adminPdo->exec("DROP DATABASE `{$fixtureDatabase}`");
}
if ($failed) {
    exit(1);
}
