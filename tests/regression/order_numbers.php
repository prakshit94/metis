<?php

// Run: php tests/regression/order_numbers.php
// Uses an isolated MySQL database; application records are never changed.
declare(strict_types=1);

use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$source = DB::connection();
$pdo = $source->getPdo();
$name = 'metis_order_numbers_test_'.bin2hex(random_bytes(6));
$config = $source->getConfig();
$config['database'] = $name;
unset($config['url'], $config['name']);
$pdo->exec("CREATE DATABASE `{$name}`");
config(['database.connections.order_numbers_test' => $config]);
$original = DB::getDefaultConnection();
DB::setDefaultConnection('order_numbers_test');
$originalUser = auth()->user();

try {
    DB::statement('CREATE TABLE teams (id BIGINT PRIMARY KEY, code VARCHAR(20))');
    DB::statement('CREATE TABLE orders (id BIGINT AUTO_INCREMENT PRIMARY KEY, order_no VARCHAR(255) UNIQUE, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL)');
    DB::table('teams')->insert([['id' => 1, 'code' => 'GJ'], ['id' => 2, 'code' => 'rj']]);
    Carbon::setTestNow(Carbon::parse('2026-10-16 12:00:00'));
    $user = new class extends User {
        public ?int $testTeamId = null;

        public function getLobTeamIdAttribute(): ?int
        {
            return $this->testTeamId;
        }
    };
    auth()->setUser($user);
    $service = $app->make(OrderService::class);
    $persist = new ReflectionMethod(OrderService::class, 'persistOrderWithUniqueNumber');
    $create = function (?int $teamId, string $expected, ?string $custom = null) use ($user, $service, $persist): void {
        $user->testTeamId = $teamId;
        $order = DB::transaction(fn () => Order::withoutEvents(
            fn () => $persist->invoke($service, ['order_no' => $custom], $custom === null)
        ));
        if ($order->order_no !== $expected) {
            throw new RuntimeException("Expected {$expected}, received {$order->order_no}");
        }
    };

    DB::table('orders')->insert(['order_no' => 'ORD-16102026-144']);
    $create(1, 'GJ161020261');
    $create(1, 'GJ161020262');
    $create(1, 'GJ161020263');
    $create(2, 'RJ161020261');
    $create(2, 'RJ161020262');
    $create(null, 'ORD161020261');
    $create(null, 'ORD161020262');
    // Deleted rows still reserve their numbers; sequence uses numeric max, not count.
    DB::table('orders')->insert([
        ['order_no' => 'GJ161020269', 'deleted_at' => null],
        ['order_no' => 'GJ1610202610', 'deleted_at' => now()],
        ['order_no' => 'GJ16102026999invalid', 'deleted_at' => null],
    ]);
    $create(1, 'GJ1610202611');
    $create(2, 'CUSTOM-123', 'CUSTOM-123');
    Carbon::setTestNow(Carbon::parse('2026-10-17 12:00:00'));
    $create(1, 'GJ171020261');
    $create(null, 'ORD171020261');
    if (! DB::table('orders')->where('order_no', 'ORD-16102026-144')->exists()) {
        throw new RuntimeException('Existing order number changed');
    }
    echo "Order number regression checks passed.\n";
} finally {
    Carbon::setTestNow();
    if ($originalUser) {
        auth()->setUser($originalUser);
    } else {
        auth()->forgetUser();
    }
    DB::setDefaultConnection($original);
    DB::purge('order_numbers_test');
    $pdo->exec("DROP DATABASE `{$name}`");
}
