<?php

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Controllers\PromotionsController;
use App\Modules\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Included by query_performance.php inside its disposable database.
if (! isset($fixtureDatabase) || ! str_starts_with($fixtureDatabase, 'metis_query_test_')) {
    throw new RuntimeException('Isolated fixture required.');
}

class ExportPerformanceViewer extends QueryPerformanceViewer
{
    public bool $allComplaints = true;

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        return $permission === 'complaints.view-all' ? $this->allComplaints : true;
    }
}

function followupStream(callable $response): array
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    ob_start();
    try {
        $response()->sendContent();
        return [ob_get_contents(), count(DB::getQueryLog())];
    } finally {
        ob_end_clean();
        DB::disableQueryLog();
    }
}

function followupRequest(array $data = []): Request
{
    global $app, $viewer;
    $request = Request::create('http://localhost/api/performance-followup', 'GET', $data);
    $request->headers->set('Accept', 'application/json');
    $request->setUserResolver(fn () => $viewer);
    $app->instance('request', $request);
    return $request;
}

$viewer = new ExportPerformanceViewer;
$viewer->setRawAttributes(User::findOrFail(1)->getAttributes(), true);
auth()->setUser($viewer);
$complaintController = new \App\Modules\Orders\Controllers\OrderComplaintController;
$orderController = new \App\Modules\Orders\Controllers\OrderController;
$complaintCsv = new ReflectionMethod($complaintController, 'generateCsvExportCallback');
$orderCsv = new ReflectionMethod($orderController, 'generateCsvExportCallback');

// CSV equivalence against the unchanged formatter fed by the original cursor.
for ($i = 1; $i <= 30; $i++) {
    DB::table('order_complaints')->insert([
        'order_id' => $i, 'customer_id' => $i % 3 ? $i : null,
        'assigned_to' => $i % 3 ? 1 : 2, 'complaint_number' => 'PERF-'.$i,
        'category' => 'product', 'priority' => 'medium', 'status' => $i % 2 ? 'open' : 'resolved',
        'subject' => 'Complaint '.$i, 'description' => 'Synthetic',
        'created_at' => $now, 'updated_at' => $now, 'resolved_at' => $i % 2 ? null : $now,
    ]);
}
DB::table('order_complaints')->insert([
    'order_id' => 1, 'complaint_number' => 'DELETED', 'category' => 'product',
    'subject' => 'Deleted', 'description' => 'Synthetic', 'deleted_at' => $now,
]);
[$legacyComplaints, $legacyComplaintQueries] = followupStream(fn () => response()->stream($complaintCsv->invoke($complaintController, \App\Modules\Orders\Models\OrderComplaint::with(['order', 'customer', 'assignee'])->cursor())));
[$exportComplaints, $complaintQueries] = followupStream(fn () => $complaintController->bulkExport(followupRequest()));
verifyQuery($legacyComplaints === $exportComplaints, 'Complaint CSV bytes changed.');
verifyQuery($complaintQueries <= 4 && $legacyComplaintQueries > 60, 'Complaint export still has per-row relationship queries.');
$viewer->allComplaints = false;
[$scopedComplaints] = followupStream(fn () => $complaintController->bulkExport(followupRequest(['status' => 'open'])));
$scopedRows = array_map('str_getcsv', array_filter(explode("\n", trim($scopedComplaints))));
verifyQuery(count($scopedRows) === 11, 'Complaint assignment/status scope changed.');
$viewer->allComplaints = true;
[$selectedComplaints] = followupStream(fn () => $complaintController->exportSelected(followupRequest(['ids' => [3, 2, 1]])));
$selectedRows = array_map('str_getcsv', array_filter(explode("\n", trim($selectedComplaints))));
verifyQuery(count($selectedRows) === 4, 'Selected complaint export changed.');

$orderQuery = Order::with(['party', 'warehouse', 'items.product', 'shipments', 'billingAddress', 'shippingAddress'])->where('id', '<=', 30)->orderByDesc('order_no');
[$legacyOrders, $legacyOrderQueries] = followupStream(fn () => response()->stream($orderCsv->invoke($orderController, $orderQuery->cursor())));
[$exportOrders, $orderQueries] = followupStream(fn () => $orderController->exportSelected(followupRequest(['ids' => range(1, 30)])));
// Selected exports have always used ID order; compare with that contract too.
[$legacySelectedOrders] = followupStream(fn () => response()->stream($orderCsv->invoke($orderController, Order::whereIn('id', range(1, 30))->orderBy('id')->cursor())));
verifyQuery($exportOrders === $legacySelectedOrders, 'Selected order CSV changed.');
[$sortedOrders, $sortedOrderQueries] = followupStream(fn () => $orderController->bulkExport(followupRequest(['sort_field' => 'order_no', 'sort_direction' => 'desc'])));
[$legacySortedOrders] = followupStream(fn () => response()->stream($orderCsv->invoke($orderController, Order::orderByDesc('order_no')->cursor())));
verifyQuery($sortedOrders === $legacySortedOrders, 'Order CSV contents or requested sorting changed.');
verifyQuery($sortedOrderQueries < 15 && $legacyOrderQueries > 90, 'Order export still has per-row queries.');
echo "PASS: byte-identical order/complaint CSVs, requested order, selected IDs, assignment/status scopes; order export {$legacyOrderQueries} queries (30 rows) to {$sortedOrderQueries} (90 rows), complaints {$legacyComplaintQueries} to {$complaintQueries}.\n";

// Span an export batch boundary, without relying on all-record hydration.
$complaintRows = [];
for ($i = 31; $i <= 535; $i++) {
    $complaintRows[] = ['order_id' => 1, 'customer_id' => 1, 'assigned_to' => 1, 'complaint_number' => 'CHUNK-'.$i, 'category' => 'product', 'priority' => 'medium', 'status' => 'open', 'subject' => 'Synthetic', 'description' => 'Synthetic', 'created_at' => $now, 'updated_at' => $now];
}
DB::table('order_complaints')->insert($complaintRows);
[$chunkCsv, $chunkQueries] = followupStream(fn () => $complaintController->bulkExport(followupRequest()));
$chunkRows = array_map('str_getcsv', array_filter(explode("\n", trim($chunkCsv))));
verifyQuery(count($chunkRows) === 536 && count(array_unique(array_column(array_slice($chunkRows, 1), 0))) === 535, 'Complaint batch boundary skipped or duplicated records.');
verifyQuery($chunkQueries <= 8, 'Complaint queries must scale by batch, not by row.');
echo "PASS: 535 complaint rows across batches with {$chunkQueries} queries and no missing/duplicate records.\n";

// Global complaint counts intentionally ignore list/assignment filters.
[$complaintsData] = queryResponse(\App\Modules\Orders\Controllers\OrderComplaintController::class, 'status=resolved&per_page=1');
verifyQuery($complaintsData['stats'] === ['total' => 535, 'open' => 520, 'in_progress' => 0, 'resolved' => 15, 'closed' => 0], 'Complaint statistics scope/types changed.');
[$emptyComplaints] = queryResponse(\App\Modules\Orders\Controllers\OrderComplaintController::class, 'status=closed');
verifyQuery($emptyComplaints['stats'] === $complaintsData['stats'], 'Empty filtered list changed global complaint statistics.');

// Supplier statistics preserve model soft deletes and SQL != semantics.
$supplierController = new \App\Modules\Inventory\Controllers\SupplierController;
$request = Request::create('http://localhost/procurement/suppliers');
$request->setUserResolver(fn () => $viewer);
$app->instance('request', $request);
DB::flushQueryLog(); DB::enableQueryLog();
$emptySuppliers = $supplierController->index($request)->getData()['stats'];
verifyQuery(count(DB::getQueryLog()) === 1 && $emptySuppliers === ['total' => 0, 'active' => 0, 'inactive' => 0], 'Empty supplier statistics query/type changed.');
DB::disableQueryLog();
foreach (['active', 'inactive', 'active'] as $i => $status) {
    DB::table('suppliers')->insert(['uuid' => Str::uuid()->toString(), 'party_code' => 'PERF-'.$i, 'firstname' => 'Supplier', 'phone' => '98800000'.$i, 'status' => $status, 'deleted_at' => $i === 2 ? $now : null]);
}
verifyQuery($supplierController->index($request)->getData()['stats'] === ['total' => 2, 'active' => 1, 'inactive' => 1], 'Supplier soft-delete statistics changed.');
echo "PASS: complaint global/filtered statistics and supplier empty/soft-delete statistics.\n";

// Original return aggregates are the reference, including numeric types and
// pagination behavior. No financial/lifecycle behavior is changed here.
$legacyReturnStats = function ($query) {
    $stats = ['total' => (clone $query)->count()];
    foreach (['pending', 'approved', 'received', 'qc_in_progress'] as $status) { $stats[$status] = (clone $query)->where('order_returns.status', $status)->count(); }
    $stats['pending_qc'] = (clone $query)->whereIn('order_returns.status', ['pending', 'approved', 'received', 'qc_in_progress'])->count();
    foreach (['completed', 'rejected'] as $status) { $stats[$status] = (clone $query)->where('order_returns.status', $status)->count(); }
    $stats['total_refunded'] = (clone $query)->sum('order_returns.refund_amount');
    $stats['total_credited'] = (clone $query)->sum('order_returns.credit_note_amount');
    return $stats;
};
foreach ([[], ['status' => 'completed'], ['status' => 'approved'], ['page' => 2], ['search' => 'nonexistent']] as $params) {
    $reference = \App\Modules\Orders\Models\OrderReturn::query()->orderByDesc('order_returns.id');
    if (isset($params['status'])) { $reference->where('status', $params['status']); }
    if (isset($params['search'])) { $reference->whereRaw('1 = 0'); }
    $reference->forPage($params['page'] ?? 1, 15);
    $expected = $legacyReturnStats($reference);
    [$actual, $count] = queryResponse(\App\Modules\Orders\Controllers\OrderReturnController::class, http_build_query($params));
    verifyQuery($actual['stats'] === $expected, 'Return statistics changed for '.json_encode($params));
}
echo "PASS: return statistics match original counts/financial types, status/search filters, empty sets and page offsets.\n";

// Requested export sorting must survive crossing a chunk boundary.
$template = (array) DB::table('orders')->where('id', 2)->first();
$orderRows = [];
for ($i = 1; $i <= 505; $i++) {
    $row = $template;
    $row['id'] = 1000 + $i;
    $row['order_no'] = sprintf('BATCH-%04d', $i);
    $orderRows[] = $row;
}
foreach (array_chunk($orderRows, 100) as $batch) { DB::table('orders')->insert($batch); }
[$batchOrderCsv, $batchOrderQueries] = followupStream(fn () => $orderController->bulkExport(followupRequest(['search' => 'BATCH-', 'sort_field' => 'order_no', 'sort_direction' => 'desc'])));
$batchOrderRows = array_map('str_getcsv', array_filter(explode("\n", trim($batchOrderCsv))));
$batchOrderIds = array_column(array_slice($batchOrderRows, 1), 0);
verifyQuery(count($batchOrderRows) === 506 && array_map('intval', $batchOrderIds) === range(1505, 1001), 'Sorted order batches skipped, duplicated or reordered rows.');
verifyQuery($batchOrderQueries <= 12, 'Order export queries must scale by batch.');
echo "PASS: 505 orders sorted across batches with {$batchOrderQueries} queries, no duplicates or omissions.\n";

[$emptyOffers] = queryResponse(PromotionsController::class, '', 'offersIndex');
verifyQuery($emptyOffers['stats'] === ['total' => 0, 'active' => 0, 'expired' => 0, 'scheduled' => 0, 'bogo' => 0, 'order_discount' => 0], 'Empty offer statistics changed.');
foreach ([
    ['type' => 'bogo', 'is_active' => true, 'starts_at' => null, 'ends_at' => null],
    ['type' => 'order_discount', 'is_active' => true, 'starts_at' => $now, 'ends_at' => $now],
    ['type' => 'order_discount', 'is_active' => false, 'starts_at' => '2026-10-17 12:00:00', 'ends_at' => '2026-10-15 12:00:00'],
] as $i => $attributes) {
    DB::table('offers')->insert(array_merge($attributes, ['name' => 'Offer '.$i]));
}
[$offers] = queryResponse(PromotionsController::class, 'status=inactive', 'offersIndex');
verifyQuery($offers['stats'] === ['total' => 3, 'active' => 2, 'expired' => 1, 'scheduled' => 1, 'bogo' => 1, 'order_discount' => 2], 'Offer date boundaries, overlapping categories or global scope changed.');
verifyQuery($offers['data']['total'] === 1, 'Offer list filtering changed.');

$tagController = new \App\Modules\Orders\Controllers\CallTagAdminController;
verifyQuery($tagController->index()->getData()['stats'] === ['total' => 0, 'active' => 0, 'inactive' => 0, 'level_1' => 0], 'Empty tag statistics changed.');
DB::table('call_tags')->insert([
    ['id' => 1, 'name' => 'Parent', 'level' => 1, 'is_active' => true, 'deleted_at' => null],
    ['id' => 2, 'name' => 'Child', 'level' => 2, 'is_active' => false, 'deleted_at' => null],
    ['id' => 3, 'name' => 'Deleted', 'level' => 1, 'is_active' => true, 'deleted_at' => $now],
]);
verifyQuery($tagController->index()->getData()['stats'] === ['total' => 2, 'active' => 1, 'inactive' => 1, 'level_1' => 1], 'Tag level/soft-delete statistics changed.');

$poController = new \App\Modules\Inventory\Controllers\PurchaseOrderController;
$request = Request::create('http://localhost/procurement/purchase-orders');
$request->setUserResolver(fn () => $viewer); $app->instance('request', $request);
verifyQuery($poController->index($request)->getData()['stats'] === ['total' => 0, 'pending' => 0, 'completed' => 0], 'Empty purchase-order statistics changed.');
DB::table('warehouses')->where('id', 1)->update(['state' => 'Gujarat']);
DB::table('warehouses')->where('id', 2)->update(['state' => 'Rajasthan']);
foreach ([['pending', 1, null], ['received', 1, null], ['pending', 2, null], ['received', 1, $now]] as $i => [$status, $warehouseId, $deletedAt]) {
    DB::table('purchase_orders')->insert(['po_number' => 'PERF-PO-'.$i, 'supplier_id' => 1, 'warehouse_id' => $warehouseId, 'status' => $status, 'deleted_at' => $deletedAt, 'created_at' => $now, 'updated_at' => $now]);
}
verifyQuery($poController->index($request)->getData()['stats'] === ['total' => 3, 'pending' => 2, 'completed' => 1], 'Purchase-order global/soft-delete statistics changed.');
$viewer->reviewState = 'Gujarat';
verifyQuery($poController->index($request)->getData()['stats'] === ['total' => 2, 'pending' => 1, 'completed' => 1], 'Purchase-order state visibility changed.');
$viewer->reviewState = null;
echo "PASS: offer null/exact date boundaries, overlapping counts, tag levels/soft deletes and purchase-order state scopes.\n";

$grnController = new \App\Modules\Inventory\Controllers\GoodsReceiptController;
verifyQuery($grnController->index($request)->getData()['stats'] === ['total' => 0, 'this_month' => 0, 'pending' => 0, 'discrepancies' => 0], 'Empty receipt statistics changed.');
foreach (['2026-09-30', '2026-10-01', '2026-10-31', '2026-11-01', '2026-10-15'] as $i => $date) {
    DB::table('goods_receipts')->insert(['grn_number' => 'PERF-GRN-'.$i, 'purchase_order_id' => 1, 'warehouse_id' => 1, 'received_date' => $date, 'deleted_at' => $i === 4 ? $now : null, 'created_at' => $now, 'updated_at' => $now]);
}
verifyQuery($grnController->index($request)->getData()['stats'] === ['total' => 4, 'this_month' => 2, 'pending' => 0, 'discrepancies' => 0], 'Receipt month boundaries or soft deletes changed.');
echo "PASS: receipt single-scan totals/month counts, empty results, exact month boundaries and soft deletes.\n";

// Exercise real documentation generation once, then prove repeated reads reuse
// the generated result. The array store cannot affect application file caches.
class CountingPerformanceDocGenerator extends \Dedoc\Scramble\Generator
{
    public int $calls = 0;
    public function __construct(private \Dedoc\Scramble\Generator $inner) {}
    public function generate(\Dedoc\Scramble\GeneratorConfig $config): \Dedoc\Scramble\GeneratorResult
    {
        $this->calls++;
        return $this->inner->generate($config);
    }
}
verifyQuery(\Illuminate\Support\ServiceProvider::$optimizeCommands['api-docs'] === 'scramble:cache', 'Deployment optimization must warm API documentation.');
verifyQuery(\Illuminate\Support\ServiceProvider::$optimizeClearCommands['api-docs'] === 'scramble:clear', 'Optimization clear must invalidate API documentation.');
$originalDocCache = config('scramble.cache');
$realDocGenerator = app(\Dedoc\Scramble\Generator::class);
$docGenerator = new CountingPerformanceDocGenerator($realDocGenerator);
app()->instance(\Dedoc\Scramble\Generator::class, $docGenerator);
config(['scramble.cache.store' => 'array', 'scramble.cache.key' => 'isolated-performance-docs']);
try {
    $start = microtime(true);
    verifyQuery(\Illuminate\Support\Facades\Artisan::call('scramble:cache') === 0, 'Documentation cache warm failed.');
    $coldMs = (microtime(true) - $start) * 1000;
    $cachedGenerator = new \Dedoc\Scramble\CacheableGenerator($docGenerator);
    $docConfig = \Dedoc\Scramble\Scramble::getGeneratorConfig(\Dedoc\Scramble\Scramble::DEFAULT_API);
    $start = microtime(true);
    $firstSpec = $cachedGenerator->generate($docConfig)->spec();
    $secondSpec = $cachedGenerator->generate($docConfig)->spec();
    $warmMs = (microtime(true) - $start) * 1000;
    verifyQuery($docGenerator->calls === 1, 'Warm documentation reads regenerated the schema; calls='.$docGenerator->calls);
    verifyQuery(json_encode($firstSpec) === json_encode($secondSpec) && isset($firstSpec['openapi']), 'Cached documentation changed its JSON schema.');
    verifyQuery(\Illuminate\Support\Facades\Artisan::call('scramble:clear') === 0, 'Documentation cache clear failed.');
    verifyQuery(cache()->store('array')->get('isolated-performance-docs:default') === null, 'Documentation cache clear did not invalidate the schema.');
    echo 'PASS: deployment documentation warm/clear; generation '.round($coldMs).' ms, two cached schema reads '.round($warmMs).' ms, one generation total.'.PHP_EOL;
} finally {
    config(['scramble.cache' => $originalDocCache]);
    app()->instance(\Dedoc\Scramble\Generator::class, $realDocGenerator);
}
