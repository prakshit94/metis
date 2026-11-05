<?php

require __DIR__.'/bootstrap.php';

use App\Modules\Inventory\Controllers\StockManagementController;
use App\Modules\Inventory\Models\Stock;
use App\Modules\Users\Models\User;
use App\Services\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Auth\Access\AuthorizationException;

Artisan::call('migrate',['--force'=>true]);
setPermissionsTeamId(null);
$admin=User::forceCreate(['name'=>'Stock audit','email'=>'stock-audit@example.invalid','password'=>bcrypt('test')]);
$admin->assignRole(Role::findOrCreate('Super Admin','web'));auth()->setUser($admin);
$wh=DB::table('warehouses')->insertGetId(['name'=>'Audit warehouse','state'=>'Gujarat']);
$other=DB::table('warehouses')->insertGetId(['name'=>'Other warehouse','state'=>'Rajasthan']);
$make=function($name,$qty,$threshold=5,$reserved=0,$allow=null,$limit=null,$productAllow=false,$productLimit=0,$default=null) use($wh) {
    $pid=DB::table('products')->insertGetId(['name'=>$name,'sku'=>'AUDIT-'.bin2hex(random_bytes(4)),'slug'=>'audit-'.bin2hex(random_bytes(5)),'selling_price'=>100,'min_stock_level'=>$threshold,'allow_overselling'=>$productAllow,'overselling_qty'=>$productLimit,'default_warehouse_id'=>$default ?? $wh]);
    $sid=DB::table('stocks')->insertGetId(['product_id'=>$pid,'warehouse_id'=>$wh,'quantity'=>$qty,'reserved_qty'=>$reserved,'allow_overselling'=>$allow,'overselling_qty'=>$limit]);
    return [$pid,$sid];
};
[$p1,$s1]=$make('Low threshold stock',10,20,2);
[$p2,$s2]=$make('Fully pending',10);
[$p3,$s3]=$make('Zero oversell override',0,5,0,true,0,true,50);
[$p4,$s4]=$make('Disabled oversell override',0,5,0,false,null,true,50);
[$p5,$s5]=$make('Inherited limit',0,5,0,true,null,true,7);
[$p6,$s6]=$make('Zero alert threshold',10,0);
[$p7,$s7]=$make('Coverage-only row',0,default:$other);
$createOrder=function($pid,$status,$qty,$deleted=false)use($wh,$admin){
    $oid=DB::table('orders')->insertGetId(['order_no'=>'STOCK-'.bin2hex(random_bytes(6)),'type'=>'sale','order_date'=>now(),'warehouse_id'=>$wh,'created_by'=>$admin->id,'status'=>$status,'deleted_at'=>$deleted ? now():null]);
    DB::table('order_items')->insert(['order_id'=>$oid,'product_id'=>$pid,'quantity'=>$qty,'unit_price'=>100,'total_amount'=>$qty*100]);return $oid;
};
$createOrder($p1,'pending',3);$createOrder($p2,'pending',10);
$delivered=$createOrder($p1,'delivered',10);
$returned=$createOrder($p1,'returned',4);
$deleted=$createOrder($p1,'delivered',100,true);
foreach([[$delivered,2],[$returned,4],[$deleted,100]] as [$oid,$qty]){
 $rid=DB::table('order_returns')->insertGetId(['order_id'=>$oid,'return_no'=>'STOCK-RET-'.$oid,'status'=>'completed']);
 DB::table('order_return_items')->insert(['order_return_id'=>$rid,'product_id'=>$p1,'requested_qty'=>$qty,'received_qty'=>$qty]);
}
$shipmentOrder=$createOrder($p1,'dispatched',2);
DB::table('shipments')->insert(['shipment_no'=>'STOCK-SHIP','order_id'=>$shipmentOrder,'delivery_attempts'=>2,'deleted_at'=>now()]);
$c=app(StockManagementController::class);$service=app(InventoryService::class);
$index=fn($extra=[])=>$c->index(requestFor(['warehouse_id'=>$wh,'per_page'=>25]+$extra,'GET'))->getData(true);
$data=$index();$byId=array_column($data['data'],null,'id');
check((float)$byId[$s1]['physical_available']===8.0 && (float)$byId[$s1]['available_for_sell']===5.0,'physical stock availability remains separate from pending-demand forecast');
check((float)$byId[$s3]['available_for_sell']===0.0 && (float)$byId[$s4]['available_for_sell']===0.0 && (float)$byId[$s5]['available_for_sell']===7.0,'zero/false warehouse overrides and inherited product limits are preserved');
check((float)$byId[$s6]['min_stock_level']===0.0 && (int)$byId[$s1]['product']['min_stock_level']===20,'actual product thresholds, including zero, reach the client');
check((float)$byId[$s1]['delivered_qty']===8.0 && (float)$byId[$s1]['delivery_attempted_qty']===0.0,'delivered totals match the existing model definition and ignore deleted orders/shipments');
check(!isset($byId[$s7]),'warehouse coverage-only zero rows remain excluded');
check($index(['stock_level'=>'in_stock'])['meta']['total']===2 && $index(['stock_level'=>'low_stock'])['meta']['total']===1 && $index(['stock_level'=>'out_of_stock'])['meta']['total']===3,'stock-level filters and summary counts use the same availability forecast');
$sorted=$index(['sort_by'=>'available','sort_dir'=>'asc']);$values=array_map('floatval',array_column($sorted['data'],'available_for_sell'));$expected=$values;sort($expected);
check($values===$expected,'availability sort matches displayed values');
$stock=new Stock;$stock->forceFill(['product_id'=>$p1,'warehouse_id'=>$wh,'delivered_qty'=>12]);$payload=null;
$queries=DB::pretend(function()use($stock,&$payload){$payload=$stock->toArray();});
check(!$queries && (float)$payload['delivered_qty']===12.0,'serializing precomputed delivered totals executes zero extra queries');
$index();DB::flushQueryLog();DB::enableQueryLog();$index();$queries=DB::getQueryLog();DB::disableQueryLog();
check(count($queries)<=8,'stock list uses a bounded query count instead of two queries per row');
foreach([['sort_dir'=>['desc']],['warehouse_id'=>[$wh]],['per_page'=>1001],['page'=>0],['stock_level'=>'invalid'],['search'=>['x']]] as $bad) invalid(fn()=>$c->index(requestFor($bad)),'malformed filter fields return validation errors');
$missing=DB::table('products')->insertGetId(['name'=>'No stock','sku'=>'NO-STOCK','slug'=>'no-stock','selling_price'=>100]);
$before=DB::table('stocks')->count();$read=$c->show(requestFor(['product_id'=>$missing,'warehouse_id'=>$wh]))->getData(true);
check($read['data']===null && DB::table('stocks')->count()===$before,'details GET does not create missing stock rows');
DB::table('stocks')->where('id',$s3)->update(['deleted_at'=>now()]);
check($c->show(requestFor(['product_id'=>$p3,'warehouse_id'=>$wh]))->getData(true)['data']===null && DB::table('stocks')->where('id',$s3)->value('deleted_at')!==null,'details GET never restores soft-deleted stock');
invalid(fn()=>$c->setStock(requestFor(['product_id'=>[$p1],'warehouse_id'=>$wh,'quantity'=>12])),'stock override IDs must be scalar integers');
invalid(fn()=>$c->setStock(requestFor(['product_id'=>$p1,'warehouse_id'=>$wh,'quantity'=>100000000000])),'stock override rejects database overflow');
$denied=$c->setStock(requestFor(['product_id'=>$p1,'warehouse_id'=>$wh,'quantity'=>1]));
check($denied->getStatusCode()===422 && (float)DB::table('stocks')->where('id',$s1)->value('quantity')===10.0,'existing reserved-stock protection remains unchanged');
config(['activitylog.enabled'=>true]);
$updated=$c->setStock(requestFor(['product_id'=>$p1,'warehouse_id'=>$wh,'quantity'=>12.5,'damaged_qty'=>1,'notes'=>'Count correction audit']));
check($updated->getStatusCode()===200 && (float)$service->getAvailableQty($p1,$wh)===10.5,'stock override uses existing inventory availability rules');
check(DB::table('activity_log')->where('description','Stock override note')->where('properties','like','%Count correction audit%')->exists(),'accepted override notes are saved in the activity audit log');
config(['activitylog.enabled'=>false]);$service->reserveStock($p1,$wh,1);
check((float)$service->getAvailableQty($p1,$wh)===9.5,'reservation service still subtracts reserved stock without forecast/overselling changes');
// Full export exceeds the old 1,000-row limit; all fixtures stay inside this disposable schema.
for($batch=0;$batch<3;$batch++){
 $products=[];for($j=1;$j<=405;$j++){$n=$batch*405+$j;$products[]=['name'=>$n===1?'=1+1':'Bulk stock '.$n,'sku'=>'BULK-STOCK-'.$n,'slug'=>'bulk-stock-'.$n,'selling_price'=>100,'default_warehouse_id'=>$wh,'min_stock_level'=>5];}DB::table('products')->insert($products);
}
$ids=DB::table('products')->where('sku','like','BULK-STOCK-%')->pluck('id');foreach($ids->chunk(400) as $chunk)DB::table('stocks')->insert($chunk->map(fn($id)=>['product_id'=>$id,'warehouse_id'=>$wh,'quantity'=>10])->all());
$expectedCount=$index()['meta']['total'];$response=$c->export(requestFor(['warehouse_id'=>$wh]));ob_start();$response->sendContent();$csv=ob_get_clean();$handle=fopen('php://memory','r+');fwrite($handle,$csv);rewind($handle);$headers=fgetcsv($handle);$rows=[];while(($row=fgetcsv($handle))!==false)$rows[]=$row;fclose($handle);
check(count($rows)===$expectedCount && count($rows)>1000,'full stock export includes all filtered rows beyond 1,000');
check(count(array_filter($rows,fn($row)=>$row[0]==="'=1+1"))===1,'CSV formula-like product names are neutralized as text');
check($headers[26]==='Physical Available' && count($rows[0])===count($headers),'export preserves stock and pipeline columns and adds physical availability');
$restricted=User::forceCreate(['name'=>'View-only stock','email'=>'stock-view-only@example.invalid','password'=>bcrypt('test')]);$restricted->givePermissionTo(Permission::findOrCreate('product-view','web'));auth()->setUser($restricted);
try{$c->export(requestFor([]));throw new RuntimeException('Unexpected export access');}catch(AuthorizationException $e){check(true,'export endpoint enforces product-export permission server-side');}

// The same warehouse/state rules apply to list, export and details.
auth()->setUser($admin);
$otherStock=DB::table('stocks')->insertGetId(['product_id'=>$p1,'warehouse_id'=>$other,'quantity'=>100]);
$scopedUser=new class extends User {
    public function hasRole($roles, ?string $guard=null): bool { return true; }
    public function getLobStateNameAttribute(): ?string { return 'Gujarat'; }
};
$scopedUser->forceFill(['id'=>$admin->id]);auth()->setUser($scopedUser);
$restrictedList=$c->index(requestFor(['warehouse_id'=>$other],'GET'))->getData(true);
check($restrictedList['meta']['total']===0,'warehouse filtering cannot expose stock from another state');
invalid(fn()=>$c->show(requestFor(['product_id'=>$p1,'warehouse_id'=>$other])),'details endpoint retains warehouse state restrictions');
$response=$c->export(requestFor(['ids'=>[$otherStock]],'GET'));ob_start();$response->sendContent();$csv=ob_get_clean();
check(count(explode("\n",trim($csv)))===1,'selected-ID export cannot bypass warehouse visibility');
$response=$c->export(requestFor(['warehouse_id'=>$wh,'ids'=>[$s1,$s2]],'GET'));ob_start();$response->sendContent();$csv=ob_get_clean();
check(count(explode("\n",trim($csv)))===3,'selected export includes exactly the requested visible records');
