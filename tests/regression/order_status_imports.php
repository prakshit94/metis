<?php

// Real import endpoints and lifecycle services, using a disposable schema only.
require __DIR__.'/bootstrap.php';

use App\Modules\Orders\Controllers\OrderController;
use App\Modules\Orders\Models\Order;
use App\Modules\Users\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::call('migrate', ['--force' => true]);
$user = User::forceCreate(['name'=>'Import tester', 'email'=>'status-import@example.invalid', 'password'=>bcrypt('Synthetic123!')]);
auth()->setUser($user);
// Mirror existing global viewer permission without altering production roles.
$role = \App\Modules\Users\Models\Role::create(['name'=>'Admin', 'guard_name'=>'web']);
$user->assignRole($role);
$party = DB::table('parties')->insertGetId(['type'=>'customer', 'firstname'=>'Import buyer']);
$warehouse = DB::table('warehouses')->insertGetId(['name'=>'Import warehouse']);
$product = DB::table('products')->insertGetId(['name'=>'Import product', 'sku'=>'IMPORT-STATUS', 'slug'=>'import-status', 'selling_price'=>100]);
DB::table('stocks')->insert(['product_id'=>$product, 'warehouse_id'=>$warehouse, 'quantity'=>50, 'dispatched_qty'=>20]);
$makeOrder = function (string $number, ?string $tracking, string $status = 'dispatched') use ($party, $warehouse, $product, $user) {
    $id = DB::table('orders')->insertGetId(['order_no'=>$number, 'status'=>$status, 'type'=>'sale', 'party_id'=>$party, 'warehouse_id'=>$warehouse, 'order_date'=>today(), 'net_amount'=>100, 'created_by'=>$user->id]);
    DB::table('order_items')->insert(['order_id'=>$id, 'product_id'=>$product, 'quantity'=>1, 'unit_price'=>100, 'total_amount'=>100]);
    if ($tracking !== null) DB::table('shipments')->insert(['shipment_no'=>'SHIP-'.$number, 'order_id'=>$id, 'tracking_no'=>$tracking, 'status'=>'in_transit']);
    return $id;
};
$one=$makeOrder('IMPORT-ONE', '000123');
$two=$makeOrder('IMPORT-TWO', null);
DB::table('shipments')->insert(['shipment_no'=>'IMPORT-SHIPMENT', 'order_id'=>$two, 'tracking_no'=>'SHIP-ONLY', 'status'=>'in_transit']);
$pending=$makeOrder('IMPORT-PENDING', 'PENDING-TRACK', 'pending');
$duplicateA=$makeOrder('IMPORT-DUP-A', 'REUSED');
$duplicateB=$makeOrder('IMPORT-DUP-B', 'REUSED');
$controller=app(OrderController::class);
$inventory=app(InventoryService::class);
$import=function (string $kind, string $csv, bool $preview=true) use ($controller,$inventory,$user) {
    $path=tempnam(sys_get_temp_dir(),'metis-import-');
    file_put_contents($path,$csv);
    try {
        $request=requestFor($preview ? ['preview'=>'1'] : []);
        $request->files->set('file', new UploadedFile($path,'orders.csv','text/csv',null,true));
        $request->setUserResolver(fn()=>$user);
        return $controller->{$kind==='deliver' ? 'importBulkDeliver' : 'importBulkReturn'}($request,$inventory)->getData(true);
    } finally { unlink($path); }
};
foreach (['deliver','return'] as $kind) {
    foreach (['tracking_id','tracking_no','tracking_number','awb'] as $header) {
        $rows=$import($kind,"$header\n000123\n")['preview'];
        check(count($rows)===1 && $rows[0]['is_valid'] && $rows[0]['order_no']==='IMPORT-ONE', "$kind accepts $header with leading zeroes");
    }
    $rows=$import($kind,"tracking_id\nSHIP-ONLY\n")['preview'];
    check($rows[0]['is_valid'] && $rows[0]['order_no']==='IMPORT-TWO', "$kind resolves shipment tracking when the order has none");
    $rows=$import($kind,"order_no,tracking_id\nIMPORT-ONE,SHIP-ONLY\n")['preview'];
    check(!$rows[0]['is_valid'], "$kind rejects mismatched order and tracking identifiers");
    $rows=$import($kind,"tracking_id\nREUSED\n")['preview'];
    check(!$rows[0]['is_valid'] && str_contains($rows[0]['error'],'Ambiguous'), "$kind rejects reused tracking across multiple orders");
    $rows=$import($kind,"tracking_id\nPENDING-TRACK\n")['preview'];
    check(!$rows[0]['is_valid'], "$kind keeps existing status eligibility rules");
    $rows=$import($kind,"\xEF\xBB\xBFIMPORT-ONE\nIMPORT-TWO\n")['preview'];
    check(count($rows)===2 && $rows[0]['is_valid'] && $rows[1]['is_valid'], "$kind preserves the first headerless row and handles BOM");
    $rows=$import($kind,"order_no,tracking_id\nIMPORT-ONE,\n,000123\n")['preview'];
    check($rows[0]['is_valid'] && !$rows[1]['is_valid'] && str_contains($rows[1]['error'],'Duplicate'), "$kind detects the same order referenced through different identifiers");
    $rows=$import($kind,"order_no\nIMPORT-ONE\n")['preview'];
    check($rows[0]['is_valid'], "$kind keeps the original order number format");
}
check(Order::find($one)->status==='dispatched' && DB::table('order_returns')->count()===0, 'previews do not mutate status or create returns');
$stockBefore=(float)DB::table('stocks')->value('dispatched_qty');
$result=$import('deliver',"tracking_id\n000123\n000123\n",false);
check($result['success'] && Order::find($one)->status==='delivered', 'tracking delivery uses the real delivery service');
check((float)DB::table('stocks')->value('dispatched_qty')===$stockBefore-1 && DB::table('order_status_logs')->where('order_id',$one)->count()===1, 'duplicate delivery does not repeat stock movement or status logs');
$result=$import('return',"tracking_id\nSHIP-ONLY\nSHIP-ONLY\n",false);
check($result['success'] && Order::find($two)->status==='return_requested' && DB::table('order_returns')->where('order_id',$two)->count()===1, 'shipment tracking return creates exactly one return');
check(DB::table('order_return_items')->count()===1 && (float)DB::table('stocks')->value('dispatched_qty')===$stockBefore-2, 'return items and in-transit stock handling are preserved');
$import('return',"tracking_id\n000123\n",false);
check(Order::find($one)->status==='return_requested' && (float)DB::table('stocks')->value('dispatched_qty')===$stockBefore-2, 'delivered return keeps the existing stock treatment');
// An ordinary user must not resolve another user's records through tracking.
$user->removeRole($role);
$user->givePermissionTo(\App\Modules\Users\Models\Permission::findOrCreate('orders.view.dispatched','web'));
DB::table('orders')->where('id',$duplicateA)->update(['created_by'=>null]);
DB::table('shipments')->where('order_id',$duplicateA)->update(['tracking_no'=>'HIDDEN']);
$user->unsetRelation('roles');
$user->unsetRelation('permissions');
$rows=$import('deliver',"tracking_id\nHIDDEN\n")['preview'];
check(!$rows[0]['is_valid'], 'tracking lookup preserves order visibility restrictions');
foreach (['deliver','return'] as $kind) {
    foreach ([null,'tracking_id'] as $identifier) {
        $response=$controller->{$kind==='deliver' ? 'importBulkDeliverTemplate' : 'importBulkReturnTemplate'}(requestFor($identifier ? ['identifier'=>$identifier] : []));
        ob_start();$response->sendContent();$csv=ob_get_clean();
        check(str_starts_with($csv,"\xEF\xBB\xBF".($identifier ?: 'order_no')), "$kind template supports ".($identifier ?: 'order_no'));
    }
}
echo "Order status import checks passed.\n";
