<?php
// Pure model arithmetic: no database writes, live customer or order is used.
require dirname(__DIR__,2).'/vendor/autoload.php';
$app=require dirname(__DIR__,2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$checks=0;
foreach ([0,5,12,18,28] as $rate) {
    foreach ([0,0.01,99.99,2000,2000.01,99999.99] as $inclusive) {
        $product=new App\Modules\Catalog\Models\Product;
        $product->setRawAttributes(['selling_price'=>round($inclusive/(1+$rate/100),2),'selling_price_including_tax'=>number_format($inclusive,2,'.','')]);
        $product->setRelation('taxRate',new App\Modules\Catalog\Models\TaxRate(['rate'=>$rate]));
        foreach ([1,2,3,100] as $qty) {
            $gross=(float)$product->selling_price*$qty*(1+$rate/100);
            if (round($gross,2)!==round($inclusive*$qty,2) || $product->selling_price_inc_gst!== (float)$inclusive) throw new RuntimeException('Inclusive conversion drift');
            $checks++;
        }
    }
}
$product->selling_price=100;
if ($product->selling_price_including_tax!==null || $product->selling_price!=='100.00') throw new RuntimeException('Legacy base-price writer retained stale inclusive price');
echo "PASS: {$checks} GST rate/price/quantity combinations and legacy price invalidation.\n";
