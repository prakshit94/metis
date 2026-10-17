<?php

// Run: php tests/regression/dashboard_reportees.php
// All writes use a disposable fixture database, never application records.
declare(strict_types=1);

use App\Modules\Customers\Models\Party;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

class DashboardReporteeViewer extends User
{
    public array $testPermissions = ['orders.view.pending'];
    public bool $testGlobalRole = false;
    public ?string $testState = 'Gujarat';

    public function can($abilities, $arguments = [])
    {
        return in_array($abilities, $this->testPermissions, true);
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        return $this->testGlobalRole;
    }

    public function getLobStateNameAttribute(): ?string
    {
        return $this->testState;
    }
}

$source = DB::connection();
$pdo = $source->getPdo();
$name = 'metis_dashboard_reportees_'.bin2hex(random_bytes(6));
$config = $source->getConfig();
$config['database'] = $name;
unset($config['url'], $config['name']);
$pdo->exec("CREATE DATABASE `{$name}`");
config(['database.connections.dashboard_reportees_test' => $config]);
$original = DB::getDefaultConnection();
DB::setDefaultConnection('dashboard_reportees_test');

try {
    DB::statement('CREATE TABLE users (id BIGINT PRIMARY KEY, manager_id BIGINT NULL, deleted_at TIMESTAMP NULL)');
    DB::statement('CREATE TABLE parties (id BIGINT PRIMARY KEY, type VARCHAR(20), created_by BIGINT, deleted_at TIMESTAMP NULL)');
    DB::statement('CREATE TABLE orders (id BIGINT PRIMARY KEY, created_by BIGINT, status VARCHAR(40), shipping_state VARCHAR(50), order_date DATETIME, deleted_at TIMESTAMP NULL)');
    foreach ([[1,null], [2,1], [3,null], [4,2], [5,1]] as [$id, $manager]) {
        DB::table('users')->insert(['id' => $id, 'manager_id' => $manager, 'deleted_at' => $id === 5 ? '2026-10-01' : null]);
    }
    // Own, direct report, unrelated, indirect report, forbidden status,
    // out-of-state report, deleted user, deleted order, future and older order.
    $fixtures = [
        [1,1,'pending','Rajasthan',null,'2026-10-17'],
        [2,2,'pending','Gujarat',null,'2026-10-17'],
        [3,3,'pending','Gujarat',null,'2026-10-17'],
        [4,4,'pending','Gujarat',null,'2026-10-17'],
        [5,2,'delivered','Gujarat',null,'2026-10-17'],
        [6,2,'pending','Rajasthan',null,'2026-10-17'],
        [7,5,'pending','Gujarat',null,'2026-10-17'],
        [8,2,'pending','Gujarat','2026-10-17','2026-10-17'],
        [9,2,'future_order','Gujarat',null,'2026-10-17'],
        [10,2,'pending','Gujarat',null,'2026-10-16'],
    ];
    foreach ($fixtures as [$id,$creator,$status,$state,$deleted,$date]) {
        DB::table('orders')->insert(['id'=>$id,'created_by'=>$creator,'status'=>$status,'shipping_state'=>$state,'deleted_at'=>$deleted,'order_date'=>$date]);
    }
    DB::table('parties')->insert([
        ['id'=>1,'type'=>'customer','created_by'=>1],
        ['id'=>2,'type'=>'customer','created_by'=>2],
    ]);

    // Exercise the actual controller scope, avoiding unrelated dashboard tables.
    $controller = file_get_contents(dirname(__DIR__, 2).'/app/Modules/Core/Controllers/PageController.php');
    $start = strpos($controller, '        $isGlobalView =');
    $end = strpos($controller, '        $baseOrderQuery =', $start);
    if ($start === false || $end === false) throw new RuntimeException('Dashboard scope not found');
    $scope = str_replace(
        ['Party::', 'Order::', 'User::'],
        ['\\'.Party::class.'::', '\\'.Order::class.'::', '\\'.User::class.'::'],
        substr($controller, $start, $end - $start)
    );
    $queries = function ($user) use ($scope) {
        eval($scope);
        return [$orderQuery, $customerQuery];
    };
    $check = function ($user, array $expected, string $message) use ($queries) {
        [$query] = $queries($user);
        $actual = $query->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
        if ($actual !== $expected) throw new RuntimeException($message.': '.json_encode($actual));
    };
    $manager = new DashboardReporteeViewer;
    $manager->id = 1;
    $check($manager, [1,2,10], 'Manager sees self and direct reportees within existing LOB/status limits');
    [$query] = $queries($manager);
    if ($query->where('order_date', '>=', '2026-10-17')->where('order_date', '<', '2026-10-18')->pluck('id')->sort()->values()->all() !== [1,2]) {
        throw new RuntimeException('Date filter must constrain both self and reportee orders');
    }
    $manager->testPermissions[] = 'orders.view.future_order';
    $check($manager, [1,2,9,10], 'Future orders use the same reporting scope');
    $manager->testPermissions = [];
    $check($manager, [], 'Reporting must not grant status permissions');
    $manager->testPermissions = ['orders.view.pending','view_all_order'];
    $check($manager, [1,2,3,4,7,10], 'View-all keeps existing status and LOB behavior');
    $manager->testPermissions = ['orders.view.pending'];
    $manager->testState = null;
    $check($manager, [1,2,6,10], 'Without LOB, reportee visibility spans states');
    [, $customers] = $queries($manager);
    if ($customers->pluck('id')->all() !== [1]) throw new RuntimeException('Customer ownership changed');
    $manager->testPermissions = ['view-all-data'];
    $check($manager, [1,2,3,4,5,6,7,9,10], 'Global permission unchanged');
    $manager->testPermissions = [];
    $manager->testGlobalRole = true;
    $check($manager, [1,2,3,4,5,6,7,9,10], 'Global role unchanged');
    $manager->testGlobalRole = false;
    $manager->testPermissions = ['orders.view.pending'];
    $manager->id = 3;
    $check($manager, [3], 'Employee without reportees remains self-only');
    // Changing Users > Manager takes effect immediately, without cached ID lists.
    DB::table('users')->where('id', 2)->update(['manager_id' => 3]);
    $check($manager, [2,3,6,10], 'Reassignment grants visibility to new manager');
    $manager->id = 1;
    $check($manager, [1], 'Reassignment removes visibility from previous manager');
    echo "PASS: dashboard self/reportee scope, unrelated/indirect exclusions, status/LOB/date restrictions, future orders, soft deletes, view-all/global access, customers unchanged and manager reassignment.\n";
} finally {
    DB::setDefaultConnection($original);
    DB::purge('dashboard_reportees_test');
    $pdo->exec("DROP DATABASE `{$name}`");
}
