<?php
require __DIR__.'/bootstrap.php';

use App\Modules\Users\Models\User;
use App\Modules\Users\Models\Team;
use App\Modules\Users\Models\Office;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\Permission;
use App\Services\OrderService;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::call('migrate', ['--force' => true]);
function officeHttp(string $method, string $uri, string $token, array $body = []): array {
    global $app;
    auth()->forgetGuards(); auth()->shouldUse('web');
    $request=Request::create($uri, $method, $body, [], [], ['HTTP_ACCEPT'=>'application/json','HTTP_AUTHORIZATION'=>'Bearer '.$token]);
    $response=$app->make(Kernel::class)->handle($request);
    return [$response->getStatusCode(),json_decode($response->getContent(),true)];
}
function officeUser(string $name): User { return User::create(['name'=>$name,'email'=>$name.'@example.invalid','password'=>'Example123!','is_active'=>true]); }
$root=officeUser('office-root'); $agent=officeUser('office-agent'); $peer=officeUser('office-peer');
$rootRole=Role::findOrCreate('Super Admin','web');
$localRole=Role::findOrCreate('State operator','web'); $secondRole=Role::findOrCreate('Second local role','web');
foreach(['team-view','team-create','team-edit','team-delete','office-view','office-create','office-edit','office-delete','user-view','user-create','user-edit','user-sync-roles'] as $permission) {
    $localRole->givePermissionTo(Permission::findOrCreate($permission,'web'));
}
setPermissionsTeamId(null);$root->assignRole($rootRole);$rootToken=$root->createToken('organization-test')->plainTextToken;
[$status,$data]=officeHttp('POST','/api/teams',$rootToken,['name'=>'Gujarat state','code'=>'gj','state_name'=>'Gujarat']);
check($status===201 && $data['data']['code']==='GJ','state creation normalizes its unique code');$gj=Team::findOrFail($data['data']['id']);
[$status,$data]=officeHttp('POST','/api/teams',$rootToken,['name'=>'Rajasthan state','code'=>'RJ']);
check($status===201 && $data['data']['resolved_state_name']==='Rajasthan','canonical code supplies its exact existing state mapping');$rj=Team::findOrFail($data['data']['id']);
[$status]=officeHttp('POST','/api/teams',$rootToken,['name'=>'Conflict','code'=>'MH','state_name'=>'Gujarat']);check($status===422,'conflicting state/code mapping rejected');
[$status]=officeHttp('POST','/api/teams',$rootToken,['name'=>'Unmapped','code'=>'ZZ']);check($status===422,'unmapped state cannot silently grant global visibility');
setPermissionsTeamId($gj->id);$agent->assignRole([$localRole,$secondRole]);$peer->assignRole($localRole);setPermissionsTeamId(null);
$agentToken=$agent->createToken('organization-test')->plainTextToken;
[$status,$data]=officeHttp('GET','/api/teams?search=Gujarat&is_active=1',$rootToken);
check($status===200 && $data['from']===1 && $data['to']===1 && $data['data'][0]['members_count']===2,'pagination bounds and distinct member counts are accurate');
check($gj->members()->count('users.id')>=2 && $gj->members()->get()->unique('id')->count()===2,'team relation resolves actual role-pivot members');
foreach([0,-1,1001,'bad'] as $size){[$status]=officeHttp('GET','/api/teams?per_page='.$size,$rootToken);check($status===422,'invalid page size rejected: '.$size);}
[$status,$data]=officeHttp('GET','/api/teams',$agentToken);check($status===200 && count($data['data'])===1 && $data['data'][0]['id']===$gj->id,'state operator lists only their state');
[$status]=officeHttp('PATCH','/api/teams/'.$rj->id,$agentToken,['description'=>'intrusion']);check($status===404,'cross-state team mutation blocked');
[$status]=officeHttp('POST','/api/teams',$agentToken,['name'=>'New','code'=>'MP']);check($status===403,'state operator cannot create another state');
[$status,$data]=officeHttp('POST','/api/offices',$rootToken,['team_id'=>$gj->id,'name'=>'Ahmedabad','code'=>'amd']);
check($status===201 && $data['data']['code']==='AMD','office is created under state with normalized code');$office=Office::findOrFail($data['data']['id']);
$villageId=DB::table('villages')->insertGetId(['village_name'=>'Office village','normalized_name'=>'office village','pincode'=>'380001','state_name'=>'Gujarat']);
[$status,$addressData]=officeHttp('PATCH','/api/offices/'.$office->id,$rootToken,[
    'address_line_1'=>'12 Main Road','address_line_2'=>'Near Market','village_id'=>$villageId,
    'village_name'=>'Office village','post_office'=>'Central PO','taluka'=>'Ahmedabad','district'=>'Ahmedabad','state'=>'Gujarat','city'=>'Ahmedabad','pincode'=>'380001']);
check($status===200 && $addressData['data']['address']==='12 Main Road' && $addressData['data']['address_line_2']==='Near Market' && $addressData['data']['village_id']===$villageId,'structured Users-style office address saves with legacy address compatibility');
[$status,$addressData]=officeHttp('PATCH','/api/offices/'.$office->id,$rootToken,['description'=>'Address retained']);
check($status===200 && $addressData['data']['village_id']===$villageId && $addressData['data']['address_line_2']==='Near Market','partial office edit preserves structured address');
[$status,$addressData]=officeHttp('PATCH','/api/offices/'.$office->id,$rootToken,['address'=>'Legacy client street']);
check($status===200 && $addressData['data']['address_line_1']==='Legacy client street' && $addressData['data']['address_line_2']==='Near Market','legacy client can update street without losing postal metadata');
[$status]=officeHttp('PATCH','/api/offices/'.$office->id,$rootToken,['village_id'=>99999999]);check($status===422,'office address rejects nonexistent village IDs');
[$status]=officeHttp('PATCH','/api/offices/'.$office->id,$rootToken,['address_line_2'=>str_repeat('x',256)]);check($status===422,'office address lengths are validated');
[$status,$addressData]=officeHttp('PATCH','/api/offices/'.$office->id,$rootToken,['village_id'=>null,'village_name'=>null]);
check($status===200 && $addressData['data']['village_id']===null && $addressData['data']['city']==='Ahmedabad','clearing a village preserves manual address fields');
[$status,$data]=officeHttp('POST','/api/offices',$rootToken,['team_id'=>$gj->id,'name'=>'Surat','code'=>'SRT']);check($status===201,'state supports multiple offices');$surat=Office::findOrFail($data['data']['id']);
[$status,$data]=officeHttp('POST','/api/offices',$rootToken,['team_id'=>$rj->id,'name'=>'Jaipur','code'=>'AMD']);check($status===201,'office codes are unique within state, not globally');$foreignOffice=Office::findOrFail($data['data']['id']);
[$status]=officeHttp('POST','/api/offices',$rootToken,['team_id'=>$gj->id,'name'=>'Duplicate','code'=>'AMD']);check($status===422,'duplicate office code within a state rejected');
[$status]=officeHttp('PATCH','/api/offices/'.$foreignOffice->id,$agentToken,['name'=>'intrusion']);check($status===404,'cross-state office mutation blocked');
[$status]=officeHttp('PATCH','/api/offices/'.$office->id,$rootToken,['team_id'=>$rj->id]);check($status===422,'office cannot be silently moved to another state');
[$status]=officeHttp('PATCH','/api/users/'.$agent->id,$rootToken,['office_id'=>$foreignOffice->id]);check($status===422,'agent cannot be assigned an office from another state');
[$status]=officeHttp('PATCH','/api/users/'.$agent->id,$rootToken,['office_id'=>$office->id]);check($status===200 && (int)$agent->fresh()->office_id===$office->id,'authorized admin can assign agent to office within their state');
[$status]=officeHttp('PATCH','/api/teams/'.$gj->id,$rootToken,['code'=>'GX']);check($status===422,'state order prefix protected when dependencies exist');
[$status]=officeHttp('DELETE','/api/teams/'.$gj->id,$rootToken);check($status===409,'state with offices and members cannot be deleted');
[$status]=officeHttp('DELETE','/api/offices/'.$office->id,$rootToken);check($status===409,'office with assigned agent cannot be deleted');
[$status,$data]=officeHttp('GET','/api/users?office_id='.$office->id,$rootToken);check($status===200 && count($data['data'])===1 && $data['data'][0]['office']['name']==='Ahmedabad','office member navigation uses scoped user directory and eager office metadata');
[$status]=officeHttp('PATCH','/api/offices/'.$surat->id,$rootToken,['is_active'=>false]);check($status===200,'office can be marked inactive without deleting history');
[$status]=officeHttp('PATCH','/api/users/'.$peer->id,$rootToken,['office_id'=>$surat->id]);check($status===422,'inactive office rejects new agent assignments');
[$status]=officeHttp('PATCH','/api/teams/'.$rj->id,$rootToken,['is_active'=>false]);check($status===200,'state deactivation retains record');
[$status,$data]=officeHttp('GET','/api/teams?search=Rajasthan&is_active=1',$rootToken);check($status===200 && count($data['data'])===0,'name search cannot bypass active-state filter');
[$status]=officeHttp('POST','/api/offices',$rootToken,['team_id'=>$rj->id,'name'=>'Disabled parent','code'=>'DP']);check($status===422,'inactive state rejects new offices');
[$status,$data]=officeHttp('GET','/api/teams?page=99',$rootToken);check($status===200 && $data['current_page']===$data['last_page'],'out-of-range page recovers to final valid page');

// Exercise the same persistence method used by browser, mobile and imports, avoiding unrelated stock effects.
$party=DB::table('parties')->insertGetId(['type'=>'customer','firstname'=>'Office test']);
$persist=new ReflectionMethod(OrderService::class,'persistOrderWithUniqueNumber');
auth()->forgetGuards();auth()->shouldUse('web');setPermissionsTeamId($gj->id);auth()->login($agent->fresh());
$payload=['type'=>'sale','party_id'=>$party,'order_date'=>now(),'created_by'=>$agent->id,'status'=>'pending'];
$first=DB::transaction(fn()=>$persist->invoke(app(OrderService::class),$payload,true));
check(str_starts_with($first->order_no,'GJ'.now()->format('dmY')) && (int)$first->office_id===$office->id && (int)$first->state_team_id===$gj->id,'agent order snapshots office and preserves state-code order prefix');
DB::table('users')->where('id',$agent->id)->update(['office_id'=>null]);
check((int)$first->fresh()->office_id===$office->id,'moving an agent never rewrites historical order office');
DB::table('offices')->where('id',$surat->id)->update(['is_active'=>true]);DB::table('users')->where('id',$agent->id)->update(['office_id'=>$surat->id]);
auth()->login($agent->fresh());
$second=DB::transaction(fn()=>$persist->invoke(app(OrderService::class),$payload+['office_id'=>$foreignOffice->id],true));
check(str_starts_with($second->order_no,'GJ'.now()->format('dmY')) && $first->order_no!==$second->order_no && (int)$second->office_id===$surat->id,'different offices share the state sequence and cannot spoof attribution');
check($agent->fresh()->lob_state_name==='Gujarat','office assignment preserves existing state-wide visibility');
[$status]=officeHttp('DELETE','/api/offices/'.$office->id,$rootToken);check($status===409,'historical order prevents office deletion even after agent moves');
check(DB::table('organization_events')->where('entity_type','office')->where('entity_id',$office->id)->where('event','created')->exists(),'organization changes have persistent actor audit records');
$unmapped=Team::create(['name'=>'Legacy unmapped','code'=>'ZZ','is_active'=>true]);
$bad=officeUser('missing-state');setPermissionsTeamId($unmapped->id);$bad->assignRole($localRole);setPermissionsTeamId(null);
try {$state=$bad->fresh()->lob_state_name;check(false,'unmapped state must fail closed');} catch (Symfony\Component\HttpKernel\Exception\HttpException $e) {check($e->getStatusCode()===403,'invalid legacy mapping fails closed instead of becoming global');}
