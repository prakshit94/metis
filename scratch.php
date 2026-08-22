<?php
$suppliers = \App\Modules\Inventory\Models\Supplier::pluck('id');
\App\Modules\Catalog\Models\Product::chunk(100, function($products) use ($suppliers) {
    foreach($products as $p) {
        $p->update(['supplier_id' => $suppliers->random()]);
    }
});
echo "Done";
