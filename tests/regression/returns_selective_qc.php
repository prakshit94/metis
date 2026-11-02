<?php

require __DIR__.'/returns_tracking_qc.php';

use App\Modules\Orders\Models\OrderReturn;
use App\Modules\Users\Models\User;
use App\Services\InventoryService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Symfony\Component\HttpKernel\Exception\HttpException;

[$selectedOrder,$selectedId]=$make('BYPASS-SELECTED','BYPASS-SELECTED','pending');
[$untouchedOrder,$untouchedId]=$make('BYPASS-UNSELECTED','BYPASS-UNSELECTED','pending');
$path=tempnam(sys_get_temp_dir(),'pending-qc-');
try {
    file_put_contents($path,"tracking_id\nBYPASS-SELECTED\nBYPASS-UNSELECTED\n");
    $request=requestFor(['is_preview'=>1,'include_pending'=>1]);
    $request->files->set('file',new UploadedFile($path,'qc.csv','text/csv',null,true));
    $preview=$controller->importBulkQc($request,app(InventoryService::class))->getData(true);
    check(count($preview['returns'])===2 && $preview['preview'][0]['requires_approval'] && $preview['preview'][0]['is_valid'], 'opt-in preview exposes pending candidates without processing them');
} finally { unlink($path); }
$selected=OrderReturn::findOrFail($selectedId);
$item=DB::table('order_return_items')->where('order_return_id',$selectedId)->value('id');
$payload=['items'=>[['id'=>$item,'received_qty'=>2,'restocked_qty'=>1,'damaged_qty'=>1]]];
check($controller->processQc(requestFor($payload),$selected,app(InventoryService::class))->getStatusCode()===422, 'pending return without explicit bypass remains blocked');
invalid(fn ()=>$controller->processQc(requestFor(['bypass_approval'=>true,'items'=>[['id'=>$item,'received_qty'=>3,'restocked_qty'=>1,'damaged_qty'=>1]]]),$selected,app(InventoryService::class)), 'bypass retains requested-quantity validation');
check($selected->fresh()->status==='pending', 'failed quantity validation never approves the return');
$failing=new class extends InventoryService {
    public function processReturnItem(int $productId,int $warehouseId,float $restockQty,float $damageQty,?int $orderReturnId=null): void { throw new RuntimeException('Simulated inventory failure'); }
};
$logs=DB::table('order_status_logs')->where('order_id',$selectedOrder)->count();
try { $controller->processQc(requestFor($payload+['bypass_approval'=>true]),$selected,$failing); throw new LogicException('Expected inventory failure'); }
catch (RuntimeException $e) { check($e->getMessage()==='Simulated inventory failure','inventory failure propagates through existing QC transaction'); }
check($selected->fresh()->status==='pending' && DB::table('order_status_logs')->where('order_id',$selectedOrder)->count()===$logs && (float)DB::table('order_return_items')->where('id',$item)->value('received_qty')===0.0, 'failed QC rolls back approval bypass, quantities and audit history');
$controller->processQc(requestFor($payload+['bypass_approval'=>true]),$selected->fresh(),app(InventoryService::class));
check($selected->fresh()->status==='completed' && OrderReturn::findOrFail($untouchedId)->status==='pending', 'only explicitly selected pending return completes QC');
check((float)DB::table('stocks')->where('product_id',$product)->value('quantity')===1.0 && DB::table('refunds')->count()===0, 'selected bypass uses existing stock and financial calculations');
check(DB::table('order_status_logs')->where('order_id',$selectedOrder)->where('notes','like','Approval bypass explicitly selected%')->exists(), 'successful bypass records the actor in the order audit trail');
check($controller->processQc(requestFor($payload+['bypass_approval'=>true]),$selected->fresh(),app(InventoryService::class))->getStatusCode()===422, 'completed returns cannot be bypassed and processed twice');
$restricted=User::forceCreate(['name'=>'QC only','email'=>'qc-only-bypass@example.invalid','password'=>bcrypt('test')]);
$restricted->givePermissionTo(Permission::findOrCreate('returns.qc','web'));
auth()->setUser($restricted);
try { $controller->processQc(requestFor(['bypass_approval'=>true]),OrderReturn::findOrFail($untouchedId),app(InventoryService::class)); throw new LogicException('Expected denied bypass'); }
catch (HttpException $e) { check($e->getStatusCode()===403,'QC permission alone cannot bypass approval'); }
auth()->setUser($user);
$controller->cancel(OrderReturn::findOrFail($untouchedId));
check(!OrderReturn::find($untouchedId) && DB::table('orders')->where('id',$untouchedOrder)->value('status')==='delivered', 'reject/cancel reuses existing cancellation and order restoration');
