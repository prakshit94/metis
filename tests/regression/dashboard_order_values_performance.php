<?php
require __DIR__.'/bootstrap.php';

use App\Modules\Core\Services\DashboardOrderReport;
use App\Modules\Orders\Models\Order;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

Schema::create('orders',function(Blueprint $t){$t->id();$t->string('type')->index();$t->string('status')->index();$t->dateTime('order_date')->index();$t->unsignedBigInteger('office_id')->nullable();$t->string('shipping_state')->nullable();foreach(['total_amount','tax_amount','discount_amount','net_amount','wallet_amount_used'] as $column)$t->decimal($column,15,2)->default(0);$t->softDeletes()->index();});
Schema::create('offices',fn(Blueprint $t)=>[$t->id(),$t->string('name')]);
Schema::create('order_returns',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('order_id')->index(),$t->string('status')->index()]);
Schema::create('payments',function(Blueprint $t){$t->id();$t->unsignedBigInteger('order_id')->index();$t->decimal('amount',15,2);$t->string('status')->index();$t->softDeletes();});
Schema::create('refunds',fn(Blueprint $t)=>[$t->id(),$t->unsignedBigInteger('order_id')->index(),$t->decimal('amount',15,2),$t->string('status')->index()]);
DB::table('offices')->insert(['id'=>1,'name'=>'Performance office']);
for($batch=0;$batch<20;$batch++) {
    $orders=[];$payments=[];$returns=[];
    for($j=1;$j<=500;$j++) {
        $id=$batch*500+$j;
        $orders[]=['id'=>$id,'type'=>'sale','status'=>$id%2 ? 'delivered':'dispatched','order_date'=>'2026-11-05 10:00:00','office_id'=>1,'shipping_state'=>$id%3 ? 'Gujarat':'Rajasthan','total_amount'=>100,'tax_amount'=>10,'discount_amount'=>5,'net_amount'=>100,'wallet_amount_used'=>10];
        $payments[]=['order_id'=>$id,'amount'=>30,'status'=>'completed'];$payments[]=['order_id'=>$id,'amount'=>20,'status'=>'completed'];
        if($id%10===0) {$returns[]=['order_id'=>$id,'status'=>'completed'];$returns[]=['order_id'=>$id,'status'=>'completed'];}
    }
    DB::table('orders')->insert($orders);DB::table('payments')->insert($payments);DB::table('order_returns')->insert($returns);
}
DB::flushQueryLog();DB::enableQueryLog();$memory=memory_get_usage(true);$start=microtime(true);
$report=app(DashboardOrderReport::class)->build(Order::where('order_date','>=','2026-11-05')->where('order_date','<','2026-11-06'));
$elapsed=round((microtime(true)-$start)*1000,2);$extraMemory=memory_get_usage(true)-$memory;$log=DB::getQueryLog();DB::disableQueryLog();
check(count($log)===1 && $report['totals']['orders']===10000,'10,000-order report stays at one SQL query');
check((float)$report['totals']['value']===1100000.0 && (float)$report['totals']['paid']===500000.0 && (float)$report['totals']['booked']===900000.0,'multiple payments and completed returns do not multiply amounts at scale');
check($extraMemory<32*1024*1024 && count($report['states'])===2,'application memory holds grouped results instead of the full order dataset');
echo 'PROFILE: '.json_encode(['orders'=>10000,'payments'=>20000,'returns'=>2000,'elapsed_ms'=>$elapsed,'extra_memory_bytes'=>$extraMemory,'queries'=>count($log)]).PHP_EOL;
