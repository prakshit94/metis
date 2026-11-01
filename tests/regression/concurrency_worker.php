<?php

require __DIR__.'/bootstrap.php';
use App\Modules\Orders\Controllers\OrderReturnController;
use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Users\Models\User;
use App\Services\BusinessSequence;
use App\Services\InventoryService;
use App\Services\OrderService;
use App\Services\Shipping\Providers\IndiaPostProvider;
use Illuminate\Validation\ValidationException;

$mode = $argv[1];
$args = json_decode($argv[2], true, 512, JSON_THROW_ON_ERROR);
if (isset($args['user'])) {
    auth()->setUser(User::findOrFail($args['user']));
}
$return = $mode === 'qc' ? OrderReturn::findOrFail($args['return']) : null;
if (isset($args['barrier'])) {
    file_put_contents($args['barrier'].'.ready.'.getmypid(), 'ready');
    $deadline = microtime(true) + 10;
    while (! is_file($args['barrier'].'.go') && microtime(true) < $deadline) {
        usleep(10000);
    }
    if (! is_file($args['barrier'].'.go')) {
        throw new RuntimeException('Concurrent test start timed out');
    }
}
try {
    $result = match ($mode) {
        'referral-activation' => app(\App\Http\Controllers\ReferralProgramController::class)->toggle($args['program'])->getStatusCode(),
        'sequence' => app(BusinessSequence::class)->next('concurrent-test', fn () => 0),
        'awb' => (new ReflectionMethod(IndiaPostProvider::class, 'generateBarcode'))
            ->invoke(new IndiaPostProvider),
        'qc' => app(OrderReturnController::class)->processQc(requestFor(['items' => [['id' => $args['item'], 'received_qty' => 1, 'restocked_qty' => 1, 'damaged_qty' => 0]]]), $return, app(InventoryService::class))->status(),
        'wallet' => app(OrderService::class)->createOrder(['order_no' => 'WALLET-'.$args['number'], 'type' => 'sale', 'party_id' => $args['party'], 'warehouse_id' => $args['warehouse'],
            'order_date' => now(), 'net_amount' => 80, 'use_wallet_balance' => true,
            'items' => [['product_id' => $args['product'], 'quantity' => 1, 'unit_price' => 80, 'total_amount' => 80]]])->wallet_amount_used,
    };
    echo json_encode(['ok' => true, 'result' => $result]);
} catch (ValidationException $error) {
    echo json_encode(['ok' => false, 'validation' => $error->errors()]);
}
