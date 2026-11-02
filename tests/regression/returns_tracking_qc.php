<?php

require __DIR__.'/bootstrap.php';

use App\Modules\Orders\Controllers\OrderReturnController;
use App\Modules\Users\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

Artisan::call('migrate', ['--force' => true]);
$user = User::forceCreate(['name'=>'QC tester','email'=>'qc-tracking@example.invalid','password'=>bcrypt('test')]);
setPermissionsTeamId(null);
$user->assignRole(Role::findOrCreate('Super Admin', 'web'));
auth()->setUser($user);
$party = DB::table('parties')->insertGetId(['type'=>'customer','firstname'=>'QC customer']);
$warehouse = DB::table('warehouses')->insertGetId(['name'=>'QC warehouse']);
$product = DB::table('products')->insertGetId(['name'=>'QC product','sku'=>'TRACK-QC','slug'=>'track-qc','selling_price'=>100]);
$make = function ($number, $tracking, $status='approved') use ($party,$warehouse,$product,$user) {
    $id=DB::table('orders')->insertGetId(['order_no'=>$number,'status'=>'returned','type'=>'sale','party_id'=>$party,'warehouse_id'=>$warehouse,'order_date'=>today(),'net_amount'=>100,'created_by'=>$user->id]);
    DB::table('shipments')->insert(['shipment_no'=>'SHIP-'.$number,'order_id'=>$id,'tracking_no'=>$tracking,'status'=>'returned']);
    $return=DB::table('order_returns')->insertGetId(['order_id'=>$id,'return_no'=>'RMA-'.$number,'status'=>$status]);
    DB::table('order_return_items')->insert(['order_return_id'=>$return,'product_id'=>$product,'requested_qty'=>2]);
    return [$id,$return];
};
[$order,$return]=$make('QC-ONE','000123');
$controller=app(OrderReturnController::class);
$import=function ($csv,$preview=true) use ($controller) {
    $path=tempnam(sys_get_temp_dir(),'tracking-qc-'); file_put_contents($path,$csv);
    try {
        $request=requestFor(['is_preview'=>$preview ? 1 : 0]);
        $request->files->set('file',new UploadedFile($path,'qc.csv','text/csv',null,true));
        return $controller->importBulkQc($request,app(InventoryService::class));
    } finally { unlink($path); }
};
foreach (['tracking_id','tracking_no','tracking_number','awb'] as $header) {
    $result=$import("$header\n000123\n")->getData(true);
    check(count($result['returns'])===1 && $result['returns'][0]['id']===$return && $result['preview'][0]['is_valid'], "$header resolves the return with leading-zero tracking IDs");
    check((float)$result['preview'][0]['received_qty']===2.0 && (float)$result['preview'][0]['restocked_qty']===2.0, 'tracking lookup preserves existing QC quantity defaults');
}
foreach ([$order,'QC-ONE'] as $id) {
    $data=$import("order_id\n$id\n")->getData(true);
    check($data['returns'][0]['id']===$return && $data['preview'][0]['is_valid'], 'database order ID and order number remain supported');
}
$legacy=$import("order_no,sku,received_qty,restocked_qty,damaged_qty\nQC-ONE,TRACK-QC,2,1,1\n")->getData(true);
check($legacy['preview'][0]['is_valid'] && (float)$legacy['preview'][0]['damaged_qty']===1.0, 'existing detailed QC CSV remains unchanged');
$make('QC-DUP-A','REUSED'); $make('QC-DUP-B','REUSED');
$data=$import("tracking_id\nREUSED\n")->getData(true);
check(!$data['preview'][0]['is_valid'] && str_contains($data['preview'][0]['error'],'Ambiguous'), 'shared tracking IDs cannot select an arbitrary order');
$make('QC-PENDING','PENDING','pending');
$pending=$import("tracking_id\nPENDING\n")->getData(true);
check(!$pending['preview'][0]['is_valid'] && str_contains($pending['preview'][0]['error'],'pending approval') && !$pending['returns'], 'pending returns explain the approval step without bypassing it');
check(DB::table('order_returns')->where('return_no','RMA-QC-PENDING')->value('status')==='pending', 'tracking lookup never auto-approves a pending return');
$make('QC-CLOSED','CLOSED','completed');
foreach (['CLOSED','UNKNOWN'] as $tracking) {
    $data=$import("tracking_id\n$tracking\n")->getData(true);
    check(!$data['preview'][0]['is_valid'] && !$data['returns'], 'missing or QC-ineligible tracking matches remain blocked');
}
check($import("tracking_id\n000123\n",false)->getStatusCode()===422, 'tracking CSV requires the existing QC preview and confirmation flow');
check($import("order_id,tracking_id\n$order,000123\n")->getStatusCode()===422, 'mixed identifier columns produce a clear validation error');
DB::table('shipments')->where('order_id',$order)->update(['deleted_at'=>now()]);
$data=$import("tracking_id\n000123\n")->getData(true);
check(!$data['preview'][0]['is_valid'], 'deleted shipment tracking is not used for lookup');
check((float)DB::table('order_return_items')->where('order_return_id',$return)->value('received_qty')===0.0 && DB::table('stocks')->count()===0 && DB::table('refunds')->count()===0, 'all identifier previews leave inventory, QC quantities and finances unchanged');
