<?php
require __DIR__.'/bootstrap.php';
use App\Modules\Core\Controllers\VillageController;
use App\Modules\Core\Models\Village;
use App\Modules\Core\Models\VillageServiceMapping;
use App\Modules\Catalog\Models\Service;
use App\Services\Shipping\Providers\IndiaPostProvider;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
Http::preventStrayRequests();
DB::statement('SET FOREIGN_KEY_CHECKS=0');
foreach(json_decode(file_get_contents(database_path('transitions/metis-20261029/audited-target-schema.json')),true)as$ddl) DB::unprepared($ddl);
DB::statement('SET FOREIGN_KEY_CHECKS=1');
class VillageAuditActor { public function __construct(public ?string $lob_state_name=null){} public function can($p):bool{return true;} }
class VillageFakePost extends IndiaPostProvider {
 public int $calls=0; public array $response=[];public bool $fail=false; public bool $cleared=false;
 public function __construct(){}
 public function getPincodeDetails(string $pincode,string $officeType='post'):array {$this->calls++;if($this->fail)throw new RuntimeException('Authentication timeout');return ['success'=>true,'data'=>$this->response];}
 public function clearAccessToken():void{$this->cleared=true;}
}
function villageRequest(array$data=[],?string$state=null,array$files=[]):Request{$r=Request::create('/api/villages','POST',$data,[],$files,['HTTP_ACCEPT'=>'application/json']);$r->setUserResolver(fn()=>new VillageAuditActor($state));return$r;}
$c=new VillageController;$fake=new VillageFakePost;
$app->instance(IndiaPostProvider::class,$fake);
$created=$c->store(villageRequest(['village_name'=>'Manual village','pincode'=>'380001','state_name'=>'Gujarat']));$v=Village::findOrFail($created->getData(true)['data']['id']);
check($created->status()===201 && $fake->calls===0 && Village::count()===1,'create saves exactly one manually entered village without external calls');
invalid(fn()=>$c->store(villageRequest(['pincode'=>'380001'])),'manual create requires a name without relying on India Post');
$c->update(villageRequest(['village_name'=>'Edited manual','pincode'=>'380001','state_name'=>'Gujarat']),$v);
check($fake->calls===0 && $v->fresh()->village_name==='Edited manual','edit preserves manual values and performs no sync');
invalid(fn()=>$c->store(villageRequest(['village_name'=>'Outside','pincode'=>'302001','state_name'=>'Rajasthan'],'Gujarat')),'state-scoped create rejects another state');
invalid(fn()=>$c->update(villageRequest(['village_name'=>'Moved','pincode'=>'380001','state_name'=>'Rajasthan'],'Gujarat'),$v),'state-scoped edit cannot move records outside its state');
$auto=$c->store(villageRequest(['village_name'=>'Scoped','pincode'=>'380002'],'Gujarat'))->getData(true);check($auto['data']['state_name']==='Gujarat','omitted state inherits the assigned state');
invalid(fn()=>$c->syncIndiaPostPincodes(villageRequest(['pincode'=>'123']),$fake),'manual sync validates six-digit pincodes');
$other=Village::create(['village_name'=>'Unrelated state','pincode'=>'380001','state_name'=>'Rajasthan']);
$service=Service::create(['code'=>'AUDIT-SHIP','name'=>'Audit carrier','is_active'=>true]);
VillageServiceMapping::create(['village_id'=>$v->id,'service_id'=>$service->id,'is_available'=>true,'priority'=>3]);
$office=['office_id'=>'TEST-OFFICE-1','office_type_code'=>'PO','pincode'=>'380001','office_name'=>'Synced office','state_name'=>'Gujarat','delivery_office_flag'=>'true'];
$fake->response=[$office,['office_id'=>'FOREIGN','office_type_code'=>'PO','pincode'=>'380001','office_name'=>'Foreign office','state_name'=>'Rajasthan']];
$result=$c->syncIndiaPostPincodes(villageRequest(['pincode'=>'380001'],'Gujarat'),$fake)->getData(true);
check($result['finished'] && $fake->calls===1 && $v->fresh()->office_id==='TEST-OFFICE-1','explicit manual sync adopts the saved stub');
check($other->fresh()->village_name==='Unrelated state' && !Village::where('office_id','FOREIGN')->exists(),'sync cannot overwrite or import other-state offices');
check(VillageServiceMapping::where('village_id',$v->id)->value('priority')===3,'manual sync preserves service assignments and priorities');
$c->syncIndiaPostPincodes(villageRequest(['pincode'=>'380001'],'Gujarat'),$fake);
check($fake->calls===1,'completed offices are not fetched or overwritten again');
$failed=Village::create(['village_name'=>'Retry me','pincode'=>'380003','state_name'=>'Gujarat','office_type_code'=>'FAILED']);$fake->response=[array_replace($office,['office_id'=>'RETRY-OFFICE','pincode'=>'380003'])];
$c->syncIndiaPostPincodes(villageRequest(['pincode'=>'380003'],'Gujarat'),$fake);
check($failed->fresh()->office_id==='RETRY-OFFICE','a failed pincode can be retried explicitly');
$transient=Village::create(['village_name'=>'Network','pincode'=>'380004','state_name'=>'Gujarat']);$fake->fail=true;
$res=$c->syncIndiaPostPincodes(villageRequest(['pincode'=>'380004'],'Gujarat'),$fake)->getData(true);
check($res['finished'] && !$res['success'] && $fake->cleared && $transient->fresh()->office_type_code==='API_ERROR','network failure stops safely for manual retry instead of an automatic loop');
check(!Cache::has(VillageController::syncCacheKey('Gujarat','running')),'terminal sync failures clear running flags');$fake->fail=false;
$lock=Cache::lock('village_indiapost_batch_lock',120);$lock->get();$res=$c->syncIndiaPostPincodes(villageRequest(['pincode'=>'380001'],'Gujarat'),$fake);check($res->status()===409,'concurrent sync batches cannot race village writes');$lock->release();
$c->syncIndiaPostPincodes(villageRequest(['action'=>'stop'],'Gujarat'),$fake);check(!Cache::has(VillageController::syncCacheKey('Rajasthan','stop')),'stop flags are isolated by state');Cache::flush();
function villageCsv(string$text):UploadedFile{$path=tempnam(sys_get_temp_dir(),'village-audit-');file_put_contents($path,$text);register_shutdown_function(fn()=>@unlink($path));return new UploadedFile($path,'villages.csv','text/csv',null,true);}
$csv=villageCsv("pincode,village_name,state_name,delivery_office_flag\n380009,CSV manual,Gujarat,Yes\n");$before=Village::count();$res=$c->import(villageRequest([], 'Gujarat',['file'=>$csv]));check($res->status()===200 && Village::count()===$before+1 && Village::where('village_name','CSV manual')->value('delivery_office_flag')===true,'CSV maps reordered headers, booleans and assigned state correctly');
$rollbackCsv="village_name,pincode,state_name\n"; for($i=0;$i<1000;$i++)$rollbackCsv.="Rollback {$i},380010,Gujarat\n"; $rollbackCsv.="Foreign,302001,Rajasthan\n"; $bad=villageCsv($rollbackCsv);$before=Village::count();invalid(fn()=>$c->import(villageRequest([],'Gujarat',['file'=>$bad])),'CSV cannot import another state');check(Village::count()===$before,'CSV errors roll back the complete import');
$empty=villageCsv('');invalid(fn()=>$c->import(villageRequest(['preview'=>true],null,['file'=>$empty])),'empty CSV returns validation instead of a server error');
invalid(fn()=>$c->index(villageRequest(['sort_by'=>['bad']])), 'malformed list filters return validation instead of server errors');
$rows=$c->index(villageRequest(['sort_by'=>'village_name','per_page'=>2],'Gujarat'))->getData(true);check(count($rows['pagination']['data'])===2 && !in_array($other->id,array_column($rows['pagination']['data'],'id')),'list pagination and statistics respect state scope');
invalid(fn()=>$c->exportSelected(villageRequest(['ids'=>[['bad']]])),'selected export validates each ID');
invalid(fn()=>$c->update(villageRequest(['village_name'=>$v->fresh()->village_name,'pincode'=>'380001','services'=>['1'=>['is_available'=>false],'01'=>['is_available'=>false]]]),$v),'service-key aliases cannot overwrite one mapping twice');
$app->bind(IndiaPostProvider::class,fn()=>throw new RuntimeException('Invalid encrypted configuration'));
$response=$c->syncIndiaPostPincodes(villageRequest(['pincode'=>'380099'],'Gujarat'));
check($response->status()===503,'bad India Post settings produce an actionable response instead of breaking saves');
$response=$c->syncIndiaPostPincodes(villageRequest(['action'=>'stop'],'Gujarat'));
check($response->status()===200,'stopping sync never depends on constructing the India Post client');
Cache::flush();$app->instance(IndiaPostProvider::class,$fake);
check(Http::recorded()->isEmpty(), 'all village audit tests make no real external HTTP calls');
$root=\App\Modules\Users\Models\User::create(['name'=>'Synthetic village API admin','email'=>'village-api@example.invalid','password'=>'SyntheticPassword123!','is_active'=>true]);
setPermissionsTeamId(null);$root->assignRole(\App\Modules\Users\Models\Role::findOrCreate('Super Admin','web'));
$token=$root->createToken('village-regression')->plainTextToken;
function villageHttp(string $uri,array $body,string $token):array {
    auth()->forgetGuards();auth()->shouldUse('web');
    $req=Request::create($uri,'POST',$body,[],[],['HTTP_ACCEPT'=>'application/json','HTTP_AUTHORIZATION'=>'Bearer '.$token]);
    $res=app(\Illuminate\Contracts\Http\Kernel::class)->handle($req);
    return [$res->getStatusCode(),json_decode($res->getContent(),true)];
}
$calls=$fake->calls;
[$status,$data]=villageHttp('/api/villages',['village_name'=>'HTTP manual village','pincode'=>'380008','state_name'=>'Gujarat'],$token);
check($status===201 && $fake->calls===$calls,'authenticated create route saves manually without constructing or calling the postal client');
$fake->response=[array_replace($office,['office_id'=>'HTTP-OFFICE','pincode'=>'380008'])];
[$status,$data]=villageHttp('/api/villages/sync-indiapost',['pincode'=>'380008'],$token);
check($status===200 && ($data['finished']??false) && $fake->calls===$calls+1,'authenticated manual sync route resolves the postal provider only on explicit sync');
check(Http::recorded()->isEmpty(),'HTTP-route checks make no external postal requests');
echo "Village manual sync regression checks complete.\n";
