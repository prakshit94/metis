<?php
// Isolated MySQL fixtures only; no application records are read or changed.
use App\Modules\Catalog\Models\Warehouse;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
function checkWarehouse($condition, $message) { if (!$condition) throw new RuntimeException($message); }
$source = DB::connection();
$pdo = $source->getPdo();
$name = 'metis_warehouse_test_'.bin2hex(random_bytes(6));
$config = $source->getConfig(); $config['database'] = $name; unset($config['url'], $config['name']);
$pdo->exec("CREATE DATABASE `{$name}`");
config(['database.connections.warehouse_test' => $config]);
$original = DB::getDefaultConnection();
DB::setDefaultConnection('warehouse_test');
try {
    DB::statement('CREATE TABLE orders (id INT PRIMARY KEY, warehouse_id INT NULL, status VARCHAR(40), net_amount DECIMAL(12,2))');
    DB::statement('CREATE TABLE warehouses (id INT PRIMARY KEY, name VARCHAR(100), deleted_at TIMESTAMP NULL)');
    DB::statement('CREATE TABLE order_returns (id INT PRIMARY KEY, order_id INT, status VARCHAR(40))');
    DB::statement('CREATE TABLE order_items (id INT PRIMARY KEY, order_id INT, product_id INT, quantity INT)');
    DB::statement('CREATE TABLE stocks (id INT PRIMARY KEY, warehouse_id INT, product_id INT, quantity INT, reserved_qty INT)');
    DB::statement('CREATE TABLE shipments (id INT PRIMARY KEY, order_id INT, delivery_attempts INT)');
    DB::table('warehouses')->insert([['id'=>1,'name'=>'First'],['id'=>2,'name'=>'Second']]);
    $statuses = ['future_order','pending','pending','pending_confirmation','confirmed','processing','ready_to_ship','dispatched','dispatched','delivered','returned','cancelled'];
    foreach ($statuses as $i=>$status) DB::table('orders')->insert(['id'=>$i+1,'warehouse_id'=>1,'status'=>$status,'net_amount'=>100]);
    DB::table('orders')->insert(['id'=>20,'warehouse_id'=>2,'status'=>'pending','net_amount'=>50]);
    DB::table('orders')->insert(['id'=>21,'warehouse_id'=>null,'status'=>'pending','net_amount'=>25]);
    DB::table('order_items')->insert([['id'=>1,'order_id'=>2,'product_id'=>1,'quantity'=>2],['id'=>2,'order_id'=>3,'product_id'=>1,'quantity'=>8]]);
    DB::table('stocks')->insert(['id'=>1,'warehouse_id'=>1,'product_id'=>1,'quantity'=>10,'reserved_qty'=>5]);
    DB::table('shipments')->insert([['id'=>1,'order_id'=>9,'delivery_attempts'=>1],['id'=>2,'order_id'=>9,'delivery_attempts'=>2]]);
    DB::table('order_returns')->insert([['id'=>1,'order_id'=>5,'status'=>'pending'],['id'=>2,'order_id'=>10,'status'=>'pending'],['id'=>3,'order_id'=>10,'status'=>'pending'],['id'=>4,'order_id'=>11,'status'=>'completed'],['id'=>5,'order_id'=>20,'status'=>'pending']]);
    $controller = file_get_contents(dirname(__DIR__,2).'/app/Modules/Orders/Controllers/OrderController.php');
    $start = strpos($controller, 'function () use ($warehouseStatsQuery) {');
    $end = strpos($controller, "\n        });", $start);
    $model = new class extends Model { protected $table = 'orders'; };
    $warehouseStatsQuery = $model->newQuery();
    // Re-evaluate after assigning the query captured by the production closure.
    $aggregate = eval('return '.str_replace('Warehouse::', '\\App\\Modules\\Catalog\\Models\\Warehouse::', substr($controller, $start, $end-$start)).'};');
    $stats = $aggregate()->keyBy(fn($row) => $row['warehouse_id'] ?? 'unassigned');
    $first = $stats[1];
    checkWarehouse($first['total'] === 12 && $first['total_amount'] === 1200.0, 'Warehouse totals must not multiply related rows');
    checkWarehouse($first['return_requested'] === 2 && $first['return_requested_amount'] === 200.0, 'Returns must span statuses and count each order once');
    checkWarehouse($first['pending'] === 1 && $first['unfulfillable'] === 1, 'Pending and stock exceptions must be disjoint');
    checkWarehouse($first['dispatched'] === 1 && $first['delivery_attempted'] === 1, 'Shipment attempts must count orders once');
    checkWarehouse($stats[2]['total'] === 1 && $stats['unassigned']['total'] === 1, 'Warehouse and unassigned groups must remain separate');
    $sum = array_sum(array_map(fn($status) => $first[$status], ['future_order','pending','unfulfillable','pending_confirmation','confirmed','processing','ready_to_ship','dispatched','delivery_attempted','delivered','returned','cancelled']));
    checkWarehouse($sum === $first['total'], 'Status breakdown must reconcile to total');
    $warehouseStatsQuery = $model->newQuery()->where('warehouse_id', 2)->where('status','pending');
    $aggregate = eval('return '.str_replace('Warehouse::', '\\App\\Modules\\Catalog\\Models\\Warehouse::', substr($controller, $start, $end-$start)).'};');
    checkWarehouse($aggregate()->count() === 1 && $aggregate()->first()['total'] === 1, 'Current filters must be preserved');
    $warehouseStatsQuery = $model->newQuery()->where('warehouse_id', 999);
    $aggregate = eval('return '.str_replace('Warehouse::', '\\App\\Modules\\Catalog\\Models\\Warehouse::', substr($controller, $start, $end-$start)).'};');
    checkWarehouse($aggregate()->isEmpty(), 'No matching orders must produce an empty summary');
    echo "PASS: warehouse totals, returns across statuses, duplicate related records, stock/attempt splits, unassigned groups and filters.\n";
} finally {
    DB::disconnect('warehouse_test'); DB::setDefaultConnection($original);
    $pdo->exec("DROP DATABASE `{$name}`");
}
