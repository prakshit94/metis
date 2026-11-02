<?php

require __DIR__.'/returns_selective_qc.php';

use Illuminate\Http\UploadedFile;
use App\Services\InventoryService;

foreach (['MIX-A'=>'approved','MIX-B'=>'approved','MIX-C'=>'pending','MIX-D'=>'pending'] as $tracking=>$status) $make($tracking,$tracking,$status);
$path=tempnam(sys_get_temp_dir(),'mixed-qc-');
try {
    file_put_contents($path,"tracking_id\nMIX-A\nMIX-B\nMIX-C\nMIX-D\n");
    $request=requestFor(['is_preview'=>1,'include_pending'=>1]);
    $request->files->set('file',new UploadedFile($path,'qc.csv','text/csv',null,true));
    $data=$controller->importBulkQc($request,app(InventoryService::class))->getData(true);
    check(count($data['returns'])===4 && count(array_filter($data['returns'],fn ($r)=>$r['status']==='approved'))===2 && count(array_filter($data['returns'],fn ($r)=>$r['status']==='pending'))===2,'mixed import retains both approved returns and both pending returns');
    check(count(array_filter($data['preview'],fn ($row)=>$row['is_valid'] && $row['return_item_id'] && $row['product_id'] && $row['identifier_lookup']))===4,'all mixed preview rows retain item and product identity for restock aggregation');
} finally { unlink($path); }
