<?php
// Product API regression fixtures live only in a temporary database.
declare(strict_types=1);
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Modules\Catalog\Controllers\ProductController;
use App\Modules\Catalog\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
function checkProduct(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
$source = DB::connection();
$pdo = $source->getPdo();
$sourceName = str_replace('`', '``', $source->getDatabaseName());
$database = 'metis_products_test_'.bin2hex(random_bytes(6));
$pdo->exec("CREATE DATABASE `{$database}`");
try {
    foreach (['products','stocks','categories','warehouses','brands','suppliers','units_of_measure','tax_rates','hsn_codes','product_attributes','product_attribute_values','product_attribute_mapping','orders','order_items'] as $table) {
        $pdo->exec("CREATE TABLE `{$database}`.`{$table}` LIKE `{$sourceName}`.`{$table}`");
    }
    $config=$source->getConfig(); $config['database']=$database; unset($config['url'],$config['name']);
    config(['database.connections.products_fixture'=>$config,'database.default'=>'products_fixture','cache.default'=>'array']);
    Illuminate\Database\Eloquent\Model::unsetEventDispatcher();
    checkProduct(DB::connection()->getDatabaseName()===$database, 'Database isolation failed');
    DB::table('categories')->insert(['id'=>1,'name'=>'Fixture','slug'=>'fixture']);
    DB::table('warehouses')->insert(['id'=>1,'name'=>'Fixture','code'=>'FIXTURE','status'=>'active']);
    DB::table('units_of_measure')->insert(['id'=>1,'name'=>'Fixture','short_name'=>'FX','slug'=>'fixture']);
    DB::table('tax_rates')->insert(['id'=>1,'name'=>'Fixture','rate'=>18]);
    DB::table('hsn_codes')->insert(['id'=>1,'code'=>'FIXTURE']);
    $controller=new ProductController;
    $user=new class { public $lob_state_name=null; public function can($permission) { return true; } };
    $payload=['name'=>'Fixture','sku'=>'FIXTURE','category_id'=>1,'uom_id'=>1,'tax_rate_id'=>1,'hsn_code_id'=>1,'default_warehouse_id'=>1,'purchase_price'=>51.27,'mrp'=>199.95,'selling_price'=>100.25,'min_stock_level'=>0,'status'=>'published','is_sku_enabled'=>false,'grade'=>'A','warehouse_is_sku_enabled'=>false,'warehouse_allow_overselling'=>true,'warehouse_overselling_qty'=>7];
    $request=function(array $data) use($user) { $request=Request::create('/api/products','POST',$data);$request->setUserResolver(fn()=>$user);return $request; };
    $result=$controller->store($request($payload))->getData(true);
    $product=Product::findOrFail($result['data']['id']);
    checkProduct($product->is_sku_enabled===false,'Disabled global SKU was not saved');
    checkProduct($product->selling_price==='100.25' && $product->purchase_price==='51.27','Decimal prices changed');
    $stock=$product->stocks()->firstOrFail();
    checkProduct($stock->is_sku_enabled===false && $stock->allow_overselling===true && (int)$stock->overselling_qty===7,'Warehouse overrides were not saved');
    $update=$payload;unset($update['warehouse_is_sku_enabled'],$update['warehouse_allow_overselling'],$update['warehouse_overselling_qty']);
    $controller->update($request($update),$product);
    checkProduct($stock->fresh()->is_sku_enabled===false && $stock->fresh()->allow_overselling===true,'Omitted overrides were reset');
    $update['grade']=null;
    foreach(['warehouse_is_sku_enabled','warehouse_allow_overselling','warehouse_overselling_qty'] as $field) $update[$field]=null;
    $controller->update($request($update),$product->fresh());
    $stock=$stock->fresh();
    checkProduct($stock->is_sku_enabled===null && $stock->allow_overselling===null && $stock->overselling_qty===null,'Explicit null must restore inherited warehouse settings');
    checkProduct($product->fresh()->grade===null,'Grade cannot be cleared');
    $controller->bulkStatus($request(['ids'=>[$product->id],'status'=>'draft']));
    checkProduct($product->fresh()->status==='draft' && $product->fresh()->is_active===false,'Draft status is inconsistent');
    $controller->bulkDisableSku($request(['ids'=>[$product->id],'warehouse_id'=>1]));
    checkProduct($stock->fresh()->is_sku_enabled===false,'Warehouse bulk disable failed');
    $controller->bulkEnableSku($request(['ids'=>[$product->id],'warehouse_id'=>1]));
    checkProduct($stock->fresh()->is_sku_enabled===true,'Warehouse bulk enable failed');
    $csv=tempnam(sys_get_temp_dir(),'products-fixture-');
    try {
        file_put_contents($csv, "name,sku,category_id,uom_id,tax_rate_id,hsn_code_id,default_warehouse_id,purchase_price,mrp,selling_price,stock,status\nFixture,FIXTURE,1,1,1,1,1,51.27,199.95,100.25,4,published\n");
        $importRequest=$request(['import_mode'=>'overwrite']);
        $importRequest->files->set('file',new Illuminate\Http\UploadedFile($csv,'products.csv','text/csv',null,true));
        checkProduct($controller->import($importRequest)->getData(true)['imported']===1,'CSV overwrite import failed');
        checkProduct((int)$stock->fresh()->quantity===4,'CSV overwrite stock changed');
        $importRequest->merge(['import_mode'=>'increment']);
        $controller->import($importRequest);
        checkProduct((int)$stock->fresh()->quantity===8,'CSV incremental stock changed');
    } finally { unlink($csv); }
    // Two warehouses: independently scoped stock, pending orders, rules and access.
    DB::table('warehouses')->where('id',1)->update(['state'=>'Home']);
    DB::table('warehouses')->insert(['id'=>2,'name'=>'Second','code'=>'SECOND','status'=>'active','state'=>'Other']);
    DB::table('stocks')->insert(['product_id'=>$product->id,'warehouse_id'=>2,'quantity'=>20,'reserved_qty'=>3,'allow_overselling'=>true,'overselling_qty'=>0,'is_sku_enabled'=>false]);
    foreach ([[1,1,2],[2,2,5]] as [$id,$warehouse,$qty]) {
        DB::table('orders')->insert(['id'=>$id,'order_no'=>'FIXTURE-'.$id,'type'=>'sale','order_date'=>now(),'warehouse_id'=>$warehouse,'status'=>'pending']);
        DB::table('order_items')->insert(['order_id'=>$id,'product_id'=>$product->id,'quantity'=>$qty,'unit_price'=>100,'total_amount'=>100*$qty]);
    }
    DB::table('orders')->insert(['id'=>3,'order_no'=>'FIXTURE-3','type'=>'sale','order_date'=>now(),'warehouse_id'=>2,'status'=>'confirmed']);
    DB::table('order_items')->insert(['order_id'=>3,'product_id'=>$product->id,'quantity'=>100,'unit_price'=>100,'total_amount'=>10000]);
    $all=$controller->show($request([]),$product->fresh())->getData(true)['data'];
    checkProduct(count($all['warehouse_stocks'])===2 && $all['pending_qty']===7 && $all['stock_qty']===28,'All warehouses must aggregate scoped quantities once');
    foreach ([false,true] as $summary) {
        $listing=$controller->index($request(['warehouse_id'=>2,'summary'=>$summary,'without_options'=>true]))->getData(true)['data'][0];
        checkProduct(count($listing['warehouse_stocks'])===1 && $listing['stock_qty']===20 && $listing['pending_qty']===5 && $listing['available_stock']===12,'Full/summary index warehouse scopes must agree');
    }
    $one=$controller->show($request(['warehouse_id'=>2]),$product->fresh())->getData(true)['data'];
    checkProduct(count($one['warehouse_stocks'])===1 && $one['available_stock']===12,'Detail scope must match aggregate scope without an extra flag');
    // Reproduce KFY-S-08: global overselling off, local overselling on, no physical stock.
    $oversellPayload=$payload;
    $oversellPayload['sku']='WAREHOUSE-OVERSELL';$oversellPayload['name']='Warehouse oversell fixture';
    $oversellPayload['is_sku_enabled']=true;$oversellPayload['warehouse_is_sku_enabled']=true;
    $oversellPayload['warehouse_overselling_qty']=100;
    $oversellProduct=Product::findOrFail($controller->store($request($oversellPayload))->getData(true)['data']['id']);
    $searchRequest=$request(['q'=>'WAREHOUSE-OVERSELL','warehouse_id'=>1,'stock'=>'in-stock','exclude_future_orders'=>true]);
    $search=$controller->searchApi($searchRequest)->getData(true);
    checkProduct($search['total']===1 && $search['data'][0]['available_stock']===100,'Warehouse overselling must be included in order search availability');
    $catalog=$controller->index($request(['q'=>'WAREHOUSE-OVERSELL','warehouse_id'=>1,'summary'=>true]))->getData(true)['data'][0];
    checkProduct($catalog['available_stock']===$search['data'][0]['available_stock'],'Catalog and order search must agree for local overrides');
    $oversellStock=$oversellProduct->stocks()->firstOrFail();
    $oversellStock->overselling_qty=0;$oversellStock->save();
    checkProduct($controller->searchApi($searchRequest)->getData(true)['data'][0]['available_stock']===0,'Explicit zero warehouse allowance must remain zero');
    $oversellProduct->allow_overselling=true;$oversellProduct->overselling_qty=20;$oversellProduct->save();
    $oversellStock->allow_overselling=false;$oversellStock->save();
    checkProduct($controller->searchApi($searchRequest)->getData(true)['data'][0]['available_stock']===0,'Local false must override global overselling');
    $oversellStock->allow_overselling=null;$oversellStock->overselling_qty=null;$oversellStock->save();
    checkProduct($controller->searchApi($searchRequest)->getData(true)['data'][0]['available_stock']===20,'Null warehouse rules must inherit the global allowance');
    $oversellStock->quantity=10;$oversellStock->reserved_qty=3;$oversellStock->save();
    DB::table('orders')->insert(['id'=>4,'order_no'=>'FIXTURE-4','type'=>'sale','order_date'=>now(),'warehouse_id'=>1,'status'=>'pending']);
    DB::table('order_items')->insert(['order_id'=>4,'product_id'=>$oversellProduct->id,'quantity'=>5,'unit_price'=>100,'total_amount'=>500]);
    checkProduct($controller->searchApi($searchRequest)->getData(true)['data'][0]['available_stock']===22,'Order search must deduct reserved and pending stock before adding the allowance');
    $otherWarehouse=$request(['q'=>'WAREHOUSE-OVERSELL','warehouse_id'=>2,'stock'=>'in-stock']);
    $oversellProduct->allow_overselling=false;$oversellProduct->save();
    checkProduct($controller->searchApi($otherWarehouse)->getData(true)['total']===0,'A local warehouse allowance must not leak into another warehouse');
    $oversellProduct->delete();
    $edit=$payload;$edit['warehouse_config_id']=2;$edit['warehouse_overselling_qty']=0;
    $controller->update($request($edit),$product->fresh());
    checkProduct((int)$product->fresh()->default_warehouse_id===1,'Editing another warehouse rule must preserve the default warehouse');
    checkProduct((int)$product->stocks()->where('warehouse_id',2)->value('overselling_qty')===0,'Zero warehouse limit must persist');
    checkProduct((float)$stock->fresh()->quantity===8.0 && (float)$product->stocks()->where('warehouse_id',2)->value('quantity')===20.0,'Rule edits must not change either warehouse quantity');
    $second=$product->stocks()->where('warehouse_id',2)->firstOrFail();$second->delete();
    $controller->bulkEnableSku($request(['ids'=>[$product->id],'warehouse_id'=>2]));
    checkProduct($second->fresh()->deleted_at===null && $second->fresh()->is_sku_enabled===true && $second->fresh()->quantity===20.0,'Bulk SKU must reuse the unique soft-deleted warehouse stock row');
    $controller->bulkDisableSku($request(['ids'=>[$product->id],'warehouse_id'=>2]));
    checkProduct($stock->fresh()->is_sku_enabled===true,'Bulk SKU must leave the other warehouse untouched');
    $user->lob_state_name='Home';
    $restricted=$controller->index($request(['summary'=>true,'without_options'=>true]))->getData(true)['data'][0];
    checkProduct(count($restricted['warehouse_stocks'])===1 && $restricted['stock_qty']===8 && $restricted['pending_qty']===2,'LOB scope must apply to rows, stock and pending totals');
    try { $controller->update($request($edit),$product->fresh());throw new RuntimeException('Foreign warehouse rule accepted'); }
    catch (Illuminate\Validation\ValidationException $expected) { checkProduct(isset($expected->errors()['warehouse_config_id']),'Wrong warehouse validation error'); }
    $user->lob_state_name='Other';
    $controller->update($request($edit),$product->fresh());
    checkProduct((int)$product->fresh()->default_warehouse_id===1,'A state-scoped edit must preserve an unchanged default in another state');
    $user->lob_state_name=null;
    $controller->destroy($request([]),$product->fresh());
    checkProduct(Product::onlyTrashed()->whereKey($product->id)->exists(),'Soft delete failed');
    $controller->restore($request([]),(string)$product->id);
    checkProduct(Product::whereKey($product->id)->exists(),'Restore failed');
    $controller->destroy($request([]),$product->fresh());
    checkProduct($controller->forceDelete($request([]),(string)$product->id)->getStatusCode()===409,'Permanent deletion must block nonzero stock');
    echo "PASS: isolated product save/SKU/price/override/grade regressions, bulk status and SKU, CSV overwrite/increment, soft delete/restore, nonzero-stock permanent-delete guard, two-warehouse totals/pending isolation, confirmed-order exclusion, independent rule editing, zero limits, restored stock rows, and state access.\n";
} finally {
    DB::purge('products_fixture');
    $pdo->exec("DROP DATABASE `{$database}`");
}
