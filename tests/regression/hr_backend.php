<?php
// Run: php tests/regression/hr_backend.php. Uses disposable MySQL fixtures only.
declare(strict_types=1);
use App\Modules\Users\Controllers\AttendanceController;
use App\Modules\Users\Controllers\LeaveController;
use App\Modules\Users\Controllers\LeaveBalanceController;
use App\Modules\Users\Controllers\HolidayController;
use App\Modules\Users\Models\Attendance;
use App\Modules\Users\Models\Leave;
use App\Modules\Users\Models\Holiday;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__,2).'/vendor/autoload.php';
$app=require dirname(__DIR__,2).'/bootstrap/app.php';$app->make(Kernel::class)->bootstrap();
class HrTestViewer extends User {
    public bool $global=true;
    public function can($abilities,$arguments=[]) {return $abilities === 'view-all-data' ? $this->global : true;}
    public function hasRole($roles,?string $guard=null):bool {return $this->global;}
    public function getLobTeamUserIds():?array {return $this->global ? null : [1,2];}
}
function hrCheck($condition,string $message):void {if(!$condition) throw new RuntimeException($message);}
$viewer=new HrTestViewer;$viewer->id=1;
$request=function(array $data=[])use($viewer){$r=Request::create('/api/test','POST',$data);$r->setUserResolver(fn()=>$viewer);return $r;};
$source=DB::connection();$pdo=$source->getPdo();$name='metis_hr_test_'.bin2hex(random_bytes(6));$config=$source->getConfig();$config['database']=$name;unset($config['name'],$config['url']);
$pdo->exec("CREATE DATABASE `{$name}`");config(['database.connections.hr_test'=>$config]);$original=DB::getDefaultConnection();DB::setDefaultConnection('hr_test');
$originalUser=auth()->user();auth()->setUser($viewer);
try {
 DB::statement('CREATE TABLE users (id BIGINT PRIMARY KEY, name VARCHAR(80), employee_id VARCHAR(30), joining_date DATE NULL, deleted_at TIMESTAMP NULL)');
 DB::statement('CREATE TABLE leaves (id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id BIGINT, leave_type VARCHAR(30), start_date DATE, end_date DATE, status VARCHAR(20), reason TEXT NULL, approved_by BIGINT NULL, approved_at TIMESTAMP NULL, applied_by BIGINT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)');
 DB::statement('CREATE TABLE leave_balances (id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id BIGINT, leave_type VARCHAR(30), total_leaves DECIMAL(8,2), used_leaves DECIMAL(8,2), is_active BOOLEAN DEFAULT 1, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)');
 DB::statement('CREATE TABLE attendances (id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id BIGINT, date DATE, check_in TIME NULL, check_out TIME NULL, status VARCHAR(20), notes TEXT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL)');
 DB::statement('CREATE TABLE holidays (id BIGINT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80), date DATE, type VARCHAR(30), created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)');
 foreach([1,2,3] as $id) DB::table('users')->insert(['id'=>$id,'name'=>'Employee '.$id,'employee_id'=>'E'.$id]);
 $leaveController=new LeaveController;$attendanceController=new AttendanceController;
 Model::withoutEvents(function()use($request,$viewer,$leaveController,$attendanceController){
  $holiday = new Holiday(['date' => '2026-10-02']);
  hrCheck($holiday->toArray()['date'] === '2026-10-02', 'Holiday dates must not shift during serialization');
  foreach(range(1,20) as $id) DB::table('leaves')->insert(['id'=>$id,'user_id'=>2,'leave_type'=>'Sick','start_date'=>'2026-10-17','end_date'=>'2026-10-17','status'=>$id<=16?'Pending':'Approved']);
  $pending=$leaveController->index($request(['status'=>'Pending','per_page'=>15]))->getData(true);
  hrCheck($pending['total']===16&&count($pending['data'])===15,'Pending filtering must happen before pagination');
  $history=$leaveController->index($request(['history'=>1]))->getData(true);
  hrCheck($history['total']===4&&count($history['data'])===4,'History counts must exclude pending rows');
  $clamp=$leaveController->index($request(['per_page'=>0]))->getData(true);hrCheck($clamp['per_page']===1,'Clamp zero page size');
  DB::table('leave_balances')->insert(['user_id'=>2,'leave_type'=>'Sick','total_leaves'=>50,'used_leaves'=>0]);
  $stale=Leave::findOrFail(1);
  $result=$leaveController->updateStatus($request(['status'=>'Approved']),$stale);
  hrCheck($result->getStatusCode()===200,'Initial approval');
  hrCheck((float)DB::table('leave_balances')->value('used_leaves')===1.0,'One-day request deducts one day');
  $repeat=$leaveController->updateStatus($request(['status'=>'Approved']),$stale);
  hrCheck($repeat->getStatusCode()===422&&(float)DB::table('leave_balances')->value('used_leaves')===1.0,'Stale model cannot double approve');
  $leaveController->bulkAction($request(['action'=>'approve','ids'=>[1,2,3]]));
  hrCheck((float)DB::table('leave_balances')->value('used_leaves')===3.0,'Bulk skips processed leaves and deducts remaining requests once');
  // A database write failure must roll back both status and deduction.
  DB::table('leave_balances')->update(['used_leaves'=>999999.99]);
  try{$leaveController->updateStatus($request(['status'=>'Approved']),Leave::findOrFail(4));throw new RuntimeException('Expected balance overflow');}catch(\Illuminate\Database\QueryException $e){}
  hrCheck(DB::table('leaves')->where('id',4)->value('status')==='Pending','Approval rolls back if balance update fails');
  DB::table('leave_balances')->update(['used_leaves'=>3]);
  $update=['user_id'=>2,'leave_type'=>'Sick','start_date'=>'2026-10-17','end_date'=>'2026-10-18','status'=>'Approved'];
  $leaveController->update($request($update),Leave::findOrFail(5));
  hrCheck((float)DB::table('leave_balances')->value('used_leaves')===5.0,'Editing a pending request to approved uses inclusive days');
  $viewer->global=false;
  try{$leaveController->updateStatus($request(['status'=>'Approved']),Leave::findOrFail(6));throw new RuntimeException('Expected denial');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){hrCheck($e->getStatusCode()===403,'Approval remains global only');}
  $filtered=$leaveController->index($request(['user_id'=>3]))->getData(true);hrCheck($filtered['total']===0,'Employee filter does not bypass LOB');
  foreach([1,2,3] as $id)DB::table('attendances')->insert(['id'=>$id,'user_id'=>$id,'date'=>'2026-10-17','status'=>'Present','check_in'=>'22:00','check_out'=>'06:00']);
  $data=$attendanceController->index($request(['user_id'=>2]))->getData(true);hrCheck($data['total']===1&&$data['data'][0]['user_id']===2,'LOB employee filter narrows attendance');
  hrCheck($data['data'][0]['total_time']==='8h 00m','Overnight duration unchanged');
  $blocked=$attendanceController->index($request(['user_id'=>3]))->getData(true);hrCheck($blocked['total']===0,'Attendance employee filter stays within LOB');
  try{$attendanceController->index($request(['date'=>'invalid-date']));throw new RuntimeException('Expected validation');}catch(ValidationException $e){}
  try{$attendanceController->exportSummary($request(['month'=>'2026-99']));throw new RuntimeException('Expected month validation');}catch(ValidationException $e){}
  $viewer->global=true;
  DB::table('attendances')->where('id',2)->update(['deleted_at'=>now()]);
  $attendanceController->bulkAction($request(['action'=>'force-delete','ids'=>[2]]));hrCheck(!DB::table('attendances')->where('id',2)->exists(),'Bulk permanent deletion includes trashed records');
  $summary=$attendanceController->exportSummary($request(['month'=>'2026-10','user_id'=>3]));ob_start();$summary->sendContent();$csv=ob_get_clean();hrCheck(str_contains($csv,'Employee 3')&&!str_contains($csv,'Employee 1'),'Export respects selected employee');
  echo "PASS: leave filtering/pagination, inclusive balances, repeated approval, bulk approval, transaction rollback, existing approval/LOB rules, attendance filters, overnight duration, date validation, trashed bulk action and employee export.\n";
 });
}finally{DB::setDefaultConnection($original);DB::purge('hr_test');$pdo->exec("DROP DATABASE `{$name}`");if($originalUser)auth()->setUser($originalUser);else auth()->forgetUser();}
