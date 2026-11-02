<?php

require __DIR__.'/bootstrap.php';

use App\Modules\Customers\Controllers\CustomerController;
use App\Modules\Orders\Controllers\OrderController;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use App\Services\RecordVisibility;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

Artisan::call('migrate', ['--force' => true]);
$user = User::forceCreate(['name' => 'Lookup agent', 'email' => 'lookup@example.invalid', 'password' => bcrypt('Test-only-password')]);
Permission::findOrCreate('orders.create', 'web');
$user->givePermissionTo('orders.create');
auth()->setUser($user);
$id = DB::table('parties')->insertGetId(['type' => 'customer', 'firstname' => 'Other owner', 'phone' => '8980145587', 'alternatemobile' => '9876543210', 'is_active' => false, 'is_blacklisted' => true]);
check(! app(RecordVisibility::class)->customers(App\Modules\Customers\Models\Party::query(), $user)->whereKey($id)->exists(), 'customer directory still hides another owners customer');
foreach (['8980145587', '9876543210', '+91 89801 45587'] as $phone) {
    $result = app(CustomerController::class)->searchByPhone(requestFor(['phone' => $phone]))->getData(true);
    check($result['found'] && str_contains($result['redirect'], 'customer_id='.$id), 'primary, alternate and country-prefixed phone lookup finds existing customer');
}
// The profile history must include other agents/states and statuses the directory hides.
$owner = User::forceCreate(['name' => 'Other agent', 'email' => 'other-lookup@example.invalid', 'password' => bcrypt('Test-only-password')]);
$orderIds = [];
foreach (['delivered', 'pending', 'cancelled', 'future_order'] as $status) {
    $orderIds[] = DB::table('orders')->insertGetId(['order_no' => 'LOOKUP-'.$status, 'type' => 'sale', 'party_id' => $id, 'order_date' => now(), 'status' => $status, 'shipping_state' => 'Rajasthan', 'created_by' => $owner->id]);
}
$otherCustomer = DB::table('parties')->insertGetId(['type' => 'customer', 'firstname' => 'Unrelated']);
DB::table('orders')->insertGetId(['order_no' => 'LOOKUP-UNRELATED', 'type' => 'sale', 'party_id' => $otherCustomer, 'order_date' => now(), 'status' => 'delivered']);
DB::table('orders')->insertGetId(['order_no' => 'LOOKUP-DELETED', 'type' => 'sale', 'party_id' => $id, 'order_date' => now(), 'status' => 'delivered', 'deleted_at' => now()]);
check(app(RecordVisibility::class)->orders(Order::query(), $user)->where('party_id', $id)->count() === 0, 'main order directory retains its restrictions');
$request = requestFor(['customer_id' => $id]);
app()->instance('request', $request);
$view = app(OrderController::class)->create();
check($view->getData()['initialCustomer']?->id === $id, 'global phone destination loads another owners inactive/blacklisted profile');
check(! $user->can('orders.edit'), 'lookup does not grant order mutation permissions');

$customer = $view->getData()['initialCustomer'];
check($customer->orders->pluck('id')->sort()->values()->all() === collect($orderIds)->sort()->values()->all(), 'profile includes other agents history and future orders but excludes unrelated/deleted orders');
check($customer->orders_count === 3, 'profile count includes all customer history excluding future orders');
try {
    app(RecordVisibility::class)->assertOrder($orderIds[0]);
    throw new RuntimeException('Order action guard was unexpectedly broadened');
} catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {
    check($e->getStatusCode() === 404, 'order detail/action visibility guard remains unchanged');
}
// An authorized customer refresh must preserve the same history and count.
DB::table('parties')->where('id', $id)->update(['created_by' => $user->id]);
$refresh = app(CustomerController::class)->show(requestFor([]), $id)->getData(true)['data'];
check(count($refresh['orders']) === 4 && $refresh['orders_count'] === 3, 'customer refresh preserves history and count');
