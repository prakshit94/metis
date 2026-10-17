<?php
// Uses disposable MySQL fixtures only; no application records are changed.
require dirname(__DIR__,2).'/vendor/autoload.php';
$app=require dirname(__DIR__,2).'/bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\{CallTag,CallTagFormField,CallLog};
use App\Modules\Users\Models\User;
use App\Modules\Orders\Controllers\{CallTagAdminController,CallTaggingController};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
function callCheck($condition,$message){if(!$condition)throw new RuntimeException($message);}
function callInvalid($callback){try{$callback();throw new RuntimeException('Expected validation failure');}catch(ValidationException $e){return $e->errors();}}
$source=DB::connection();$pdo=$source->getPdo();$database='metis_call_test_'.bin2hex(random_bytes(5));$config=$source->getConfig();$config['database']=$database;unset($config['name'],$config['url']);$pdo->exec("CREATE DATABASE `$database`");config(['database.connections.call_test'=>$config]);$original=DB::getDefaultConnection();DB::setDefaultConnection('call_test');
$oldUser=auth()->user();$user=new User;$user->id=1;auth()->setUser($user);
$request=function($data=[])use($user){$request=Request::create('/calls','POST',$data);$request->setUserResolver(fn()=>$user);return $request;};
try{
 DB::statement('CREATE TABLE users (id BIGINT UNSIGNED PRIMARY KEY, name VARCHAR(100), deleted_at TIMESTAMP NULL)');
 DB::statement('CREATE TABLE parties (id BIGINT UNSIGNED PRIMARY KEY)');
 DB::statement('CREATE TABLE crops (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, name VARCHAR(100), is_active BOOLEAN)');
 foreach(['142033_create_call_tags_table','142034_create_call_tag_form_fields_table','142035_create_call_logs_table','142036_create_call_log_metas_table']as$file)(require dirname(__DIR__,2).'/database/migrations/2026_07_25_'.$file.'.php')->up();
 DB::table('crops')->insert([['name'=>'Cotton','is_active'=>true],['name'=>'Inactive Crop','is_active'=>false]]);
 DB::table('users')->insert(['id'=>1,'name'=>'Fixture Agent']);DB::table('parties')->insert(['id'=>1]);
 Model::withoutEvents(function()use($request){
  $admin=new CallTagAdminController;$calls=new CallTaggingController;
  $root=$admin->store($request(['name'=>'Fixture root','level'=>1]))->getData(true)['tag'];
  $other=$admin->store($request(['name'=>'Other root','level'=>1]))->getData(true)['tag'];
  $fields=[['name'=>'remarks','label'=>'Remarks','type'=>'text','is_required'=>true,'validation_rules'=>'required|string|min:3'],['name'=>'product','label'=>'Product','type'=>'product_search','validation_rules'=>'nullable|string'],['name'=>'crop','label'=>'Crop','type'=>'multi_select','is_required'=>true,'options'=>'["Old crop"]','validation_rules'=>'required|array']];
  $subject=$admin->store($request(['name'=>'Fixture subject','level'=>2,'parent_id'=>$root['id'],'form_fields'=>$fields]))->getData(true)['tag'];
  $outcome=$admin->store($request(['name'=>'Fixture outcome','level'=>3,'parent_id'=>$subject['id']]))->getData(true)['tag'];
  callInvalid(fn()=>$admin->store($request(['name'=>'Wrong level','level'=>3,'parent_id'=>$root['id']])));
  callInvalid(fn()=>$admin->store($request(['name'=>'Missing parent','level'=>2])));
  callInvalid(fn()=>$admin->store($request(['name'=>'Duplicate fields','level'=>2,'parent_id'=>$root['id'],'form_fields'=>[$fields[0],$fields[0]]])));
  callInvalid(fn()=>$admin->store($request(['name'=>'Invalid options','level'=>2,'parent_id'=>$root['id'],'form_fields'=>[['name'=>'choice','label'=>'Choice','type'=>'select','options'=>'{"a":"bad"}']]])));
  $tag=CallTag::findOrFail($subject['id']);$admin->update($request(['name'=>'Renamed subject']),$tag);
  callCheck($tag->formFields()->count()===3,'Omitted field definitions must be preserved on update');
  $payload=['customer_id'=>1,'tag_l1_id'=>$root['id'],'tag_l2_id'=>$subject['id'],'tag_l3_id'=>$outcome['id'],'meta'=>['remarks'=>'Proper notes','product'=>['Fixture product'],'crop'=>['Cotton']],'notes'=>'Fixture only'];
  callInvalid(fn()=>$calls->storeCallLog($request([...$payload,'tag_l1_id'=>$other['id']])));
  callInvalid(fn()=>$calls->storeCallLog($request([...$payload,'meta'=>[]])));
  callInvalid(fn()=>$calls->storeCallLog($request([...$payload,'meta'=>['remarks'=>'a']])));
  callInvalid(fn()=>$calls->storeCallLog($request([...$payload,'meta'=>['remarks'=>'Valid','unknown'=>'value']])));
  callInvalid(fn()=>$calls->storeCallLog($request([...$payload,'duration_seconds'=>-1])));
  callInvalid(fn()=>$calls->storeCallLog($request([...$payload,'customer_id'=>999])));
  callInvalid(fn()=>$calls->storeCallLog($request([...$payload,'meta'=>[...$payload['meta'],'crop'=>['Inactive Crop']]])));
  callInvalid(fn()=>$calls->storeCallLog($request([...$payload,'meta'=>[...$payload['meta'],'crop'=>[['bad']]]])));
  callCheck(CallLog::count()===0,'Invalid calls must not persist');
  $response=$calls->storeCallLog($request($payload));callCheck($response->getStatusCode()===200,'Valid call should save');
  $log=CallLog::first();callCheck((int)$log->agent_id===1&&(int)$log->customer_id===1,'Call must preserve customer and authenticated agent');
  callCheck(json_decode($log->metas()->where('key','product')->value('value'),true)===['Fixture product'],'Product search arrays must serialize');
  $response=$calls->storeCallLog($request([...$payload,'tag_l3_id'=>null]));callCheck($response->getStatusCode()===200,'Outcome remains optional');
  DB::unprepared("CREATE TRIGGER call_meta_failure BEFORE INSERT ON call_log_metas FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fixture failure'");
  try{$response=$calls->storeCallLog($request($payload));callCheck($response->getStatusCode()===500&&CallLog::count()===2,'Meta failures must roll back the entire call');}finally{DB::unprepared('DROP TRIGGER call_meta_failure');}
  $admin->bulkAction($request(['action'=>'deactivate','ids'=>[$root['id']]]));
  $tagRequest=$request();$tagRequest->query->set('parent_id',$subject['id']);
  try{$calls->getTags($tagRequest);throw new RuntimeException('Expected inactive ancestor rejection');}catch(Illuminate\Database\Eloquent\ModelNotFoundException $e){}
  $admin->destroy(CallTag::findOrFail($root['id']));
  callCheck(!CallTag::whereIn('id',[$root['id'],$subject['id'],$outcome['id']])->exists(),'Parent removal must soft-delete the full branch');
  callCheck(CallTag::withTrashed()->whereIn('id',[$root['id'],$subject['id'],$outcome['id']])->count()===3,'Historical tags must remain stored');
  callCheck(CallLog::count()===2,'Tag deletion cannot remove historical call logs');
  callCheck(CallLog::with(['tagL1','tagL2','tagL3'])->first()->tagL2?->name==='Renamed subject','Historical call logs retain deleted tag labels');
  echo "PASS: tag hierarchy, definition validation/preservation, required details, authenticated ownership, optional outcome, search arrays, inactive ancestors, atomic call/meta rollback and historical soft deletion.\n";
 });
}finally{DB::setDefaultConnection($original);DB::purge('call_test');$pdo->exec("DROP DATABASE `$database`");if($oldUser)auth()->setUser($oldUser);else auth()->forgetUser();}
