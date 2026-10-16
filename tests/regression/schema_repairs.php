<?php

// Run: php tests/regression/schema_repairs.php
// Creates and drops a uniquely named, empty MySQL test database. Never migrates
// or changes application records in the database configured in .env.

declare(strict_types=1);

use App\Console\Commands\RepairConsolidatedSchema;
use App\Modules\Core\Controllers\PageController;
use App\Modules\Orders\Controllers\OrderController;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\Payment;
use App\Modules\Orders\Models\Shipment;
use App\Services\InventoryService;
use App\Services\InvoiceService;
use App\Services\OrderService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    }
}

$source = DB::connection();
check($source->getDriverName() === 'mysql', 'This regression script requires MySQL.');
$testDatabase = 'metis_regression_'.bin2hex(random_bytes(6));
$connection = $source->getConfig();
$connection['database'] = $testDatabase;
unset($connection['url'], $connection['name']);
$source->statement("CREATE DATABASE `{$testDatabase}`");

$failed = false;
try {
    config([
        'database.connections.schema_regression' => $connection,
        'database.connections.mysql' => $connection,
        'cache.default' => 'array',
        'activitylog.database_connection' => 'schema_regression',
    ]);
    DB::purge('mysql');
    DB::setDefaultConnection('schema_regression');
    Schema::clearResolvedInstance('db.schema');
    check(Schema::getConnection()->getDatabaseName() === $testDatabase, 'Schema connection is not isolated.');
    check(DB::connection()->getName() === 'schema_regression', 'Test connection name is not isolated.');
    echo "Running fresh/safety migration check in the temporary database...\n";
    check(Artisan::call('migrate', ['--force' => true, '--database' => 'schema_regression']) === 0, 'Fresh migrations failed.');
    $tables = Schema::getTableListing($testDatabase);
    check(count($tables) === 106, 'Expected 105 application tables and migration history.');
    echo "PASS: fresh migrations create every table.\n";

    Schema::table('shipments', fn (Blueprint $table) => $table->dropColumn('india_post_tracking_response'));
    Schema::table('shipment_tracking_events', function (Blueprint $table): void {
        $table->dropIndex(['provider']);
        $table->dropIndex(['provider_event_code']);
        $table->dropColumn(['provider', 'provider_event_code', 'provider_office_id', 'provider_remarks', 'provider_is_rts', 'provider_payload']);
    });
    $command = new ReflectionMethod(RepairConsolidatedSchema::class, 'splitMigrations');
    $groups = $command->invoke(new RepairConsolidatedSchema);
    $splitNames = array_merge(...array_values(array_map('array_values', $groups)));
    DB::table('migrations')->whereIn('migration', $splitNames)->delete();
    DB::table('migrations')->insert(['migration' => '2026_06_01_000100_create_chat_module_tables', 'batch' => 1]);

    check(Artisan::call('schema:repair-consolidated') === 0, 'Repair preview failed.');
    check(! Schema::hasColumn('shipments', 'india_post_tracking_response'), 'Preview modified the schema.');
    check(! DB::table('migrations')->whereIn('migration', $splitNames)->exists(), 'Preview modified migration history.');
    check(Artisan::call('schema:repair-consolidated', ['--apply' => true]) === 0, 'Schema repair failed.');
    check(Schema::hasColumn('shipments', 'india_post_tracking_response'), 'Shipment repair missing.');
    check(Schema::hasColumns('shipment_tracking_events', ['provider', 'provider_event_code', 'provider_office_id', 'provider_remarks', 'provider_is_rts', 'provider_payload']), 'Event repair incomplete.');
    check(Schema::hasIndex('shipment_tracking_events', 'shipment_tracking_events_provider_index'), 'Provider index missing.');
    check(Schema::hasIndex('shipment_tracking_events', 'shipment_tracking_events_provider_event_code_index'), 'Provider event index missing.');
    check(DB::table('migrations')->whereIn('migration', $splitNames)->count() === 13, 'Split migration history incomplete.');
    check(Artisan::call('schema:repair-consolidated', ['--apply' => true]) === 0, 'Repeated repair failed.');
    check(str_contains(Artisan::output(), 'no changes needed'), 'Repair is not idempotent.');
    echo "Running fresh/safety migration check in the temporary database...\n";
    check(Artisan::call('migrate', ['--force' => true, '--database' => 'schema_regression']) === 0, 'Normal migration after reconciliation failed.');
    check(str_contains(Artisan::output(), 'Nothing to migrate'), 'Split tables would be recreated.');
    echo "PASS: preview, additive repair, repeat run, and migration-history reconciliation.\n";

    $orderId = DB::table('orders')->insertGetId([
        'order_no' => 'REGRESSION-1', 'type' => 'sale', 'order_date' => now(), 'status' => 'pending',
    ]);
    $shipmentId = DB::table('shipments')->insertGetId(['order_id' => $orderId, 'shipment_no' => 'REGRESSION-SHIPMENT']);
    $shipment = Shipment::findOrFail($shipmentId);
    check($shipment->india_post_tracking_response === null, 'Nullable tracking response not readable.');
    $shipment->update(['status' => 'in_transit', 'india_post_tracking_response' => ['test' => true]]);
    check($shipment->fresh()->india_post_tracking_response === ['test' => true], 'Tracking JSON or shipment activity logging failed.');
    echo "PASS: existing shipments update with strict attributes and activity logging.\n";

    // A failed bulk item must roll back its earlier writes while the next item succeeds.
    $secondOrderId = DB::table('orders')->insertGetId([
        'order_no' => 'REGRESSION-2', 'type' => 'sale', 'order_date' => now(), 'status' => 'pending',
    ]);
    $service = new class($orderId) extends InventoryService {
        public function __construct(private int $failId) {}
        public function confirmOrder(Order $order): void
        {
            DB::table('orders')->where('id', $order->id)->update(['status' => 'confirmed']);
            if ($order->id === $this->failId) {
                throw new RuntimeException('Simulated failure after a write');
            }
        }
    };
    $request = Request::create('/orders/bulk-status', 'POST', [
        'order_ids' => [$orderId, $secondOrderId], 'status' => 'confirmed',
    ]);
    $request->headers->set('Accept', 'application/json');
    $response = (new OrderController)->bulkStatus($request, $service, app(OrderService::class), app(InvoiceService::class));
    check(str_contains($response->getData(true)['message'], 'Success: 1, Skipped: 1'), 'Incorrect mixed batch result.');
    check(DB::table('orders')->where('id', $orderId)->value('status') === 'pending', 'Failed order write was committed.');
    check(DB::table('orders')->where('id', $secondOrderId)->value('status') === 'confirmed', 'Successful order was rolled back.');
    echo "PASS: failed bulk orders roll back independently.\n";

    $warehouseId = DB::table('warehouses')->insertGetId(['name' => 'Test', 'code' => 'REGRESSION']);
    $productId = DB::table('products')->insertGetId(['name' => 'Test', 'sku' => 'REGRESSION', 'slug' => 'regression']);
    foreach (['2026-01-01 12:00:00', '2026-02-01 12:00:00', '2026-03-01 12:00:00'] as $date) {
        DB::table('stock_movements')->insert(['product_id' => $productId, 'warehouse_id' => $warehouseId, 'quantity' => 5, 'type' => 'in', 'created_at' => $date, 'updated_at' => $date]);
    }
    $method = new ReflectionMethod(PageController::class, 'exportStockLedger');
    ob_start();
    try {
        $method->invoke(new PageController, '2026-02-01', '2026-02-28')->sendContent();
        $csv = ob_get_contents();
    } finally {
        ob_end_clean();
    }
    $rows = array_map('str_getcsv', array_filter(explode("\n", trim($csv))));
    check(count($rows) === 2, 'Ledger date range did not filter rows.');
    check(count($rows[0]) === 8 && count($rows[1]) === 8, 'Ledger CSV columns do not match.');
    check($rows[1][5] === '' && $rows[1][7] === '', 'Unrecorded ledger balances must be blank.');
    check(str_starts_with($rows[1][0], '2026-02-01'), 'Wrong ledger date exported.');
    echo "PASS: stock ledger exports recorded fields and respects date filters.\n";

    // Both absent and partially installed accounting schemas should be optional.
    Schema::create('accounting_transactions', fn (Blueprint $table) => $table->id());
    $payment = Payment::create(['payment_no' => 'REGRESSION-PAYMENT', 'amount' => 1, 'payment_method' => 'cash', 'payment_date' => now(), 'status' => 'completed']);
    $payment->delete();
    echo "PASS: incomplete optional accounting schema does not break payments.\n";
} catch (Throwable $e) {
    $failed = true;
    fwrite(STDERR, "FAIL: ".$e->getMessage()."\n");
} finally {
    DB::disconnect('schema_regression');
    $source->statement("DROP DATABASE `{$testDatabase}`");
}

if ($failed) {
    exit(1);
}
