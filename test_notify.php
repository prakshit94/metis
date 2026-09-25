<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$order = \App\Modules\Orders\Models\Order::first();
$usersToNotify = \App\Modules\Users\Models\User::role(['Admin', 'Super Admin', 'Support'])->get();
$order->loadMissing('creator');
if ($order->creator && !$usersToNotify->contains('id', $order->creator->id)) {
    $usersToNotify->push($order->creator);
}
echo "Users to notify count: " . $usersToNotify->count() . "\n";
foreach($usersToNotify as $u) {
    echo $u->email . "\n";
}
