<?php

require __DIR__.'/bootstrap.php';

use App\Modules\Customers\Controllers\CustomerController;
use App\Modules\Orders\Controllers\OrderController;
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
$request = requestFor(['customer_id' => $id]);
app()->instance('request', $request);
$view = app(OrderController::class)->create();
check($view->getData()['initialCustomer']?->id === $id, 'global phone destination loads another owners inactive/blacklisted profile');
check(! $user->can('orders.edit'), 'lookup does not grant order mutation permissions');
