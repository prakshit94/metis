<?php
// Run: php tests/regression/hr_backend.php. Uses disposable MySQL fixtures only.
declare(strict_types=1);
use App\Modules\Users\Controllers\AttendanceController;
use App\Modules\Users\Controllers\AttendanceCorrectionController;
use App\Modules\Users\Models\AttendanceCorrection;
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
 DB::statement('CREATE TABLE users (id BIGINT UNSIGNED PRIMARY KEY, manager_id BIGINT NULL, name VARCHAR(80), employee_id VARCHAR(30), joining_date DATE NULL, deleted_at TIMESTAMP NULL)');
 DB::statement('CREATE TABLE leaves (id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id BIGINT, leave_type VARCHAR(30), start_date DATE, end_date DATE, status VARCHAR(20), reason TEXT NULL, approved_by BIGINT NULL, approved_at TIMESTAMP NULL, applied_by BIGINT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)');
 DB::statement('CREATE TABLE leave_balances (id BIGINT AUTO_INCREMENT PRIMARY KEY, user_id BIGINT, leave_type VARCHAR(30), total_leaves DECIMAL(8,2), used_leaves DECIMAL(8,2), is_active BOOLEAN DEFAULT 1, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)');
 DB::statement('CREATE TABLE attendances (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, user_id BIGINT, date DATE, check_in TIME NULL, check_out TIME NULL, status VARCHAR(20), notes TEXT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL, deleted_at TIMESTAMP NULL)');
 DB::statement('CREATE TABLE holidays (id BIGINT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(80), date DATE, type VARCHAR(30), created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)');
 (require dirname(__DIR__,2).'/database/migrations/2026_10_17_180000_create_attendance_corrections_table.php')->up();
 (require dirname(__DIR__,2).'/database/migrations/2026_10_17_210000_create_employee_rosters_table.php')->up();
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
  try{$leaveController->updateStatus($request(['status'=>'Approved']),Leave::findOrFail(6));throw new RuntimeException('Expected denial');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){hrCheck($e->getStatusCode()===403,'Unassigned reviewer cannot approve');}
  $filtered=$leaveController->index($request(['user_id'=>3]))->getData(true);hrCheck($filtered['total']===0,'Employee filter does not bypass LOB');
  foreach([1,2,3] as $id)DB::table('attendances')->insert(['id'=>$id,'user_id'=>$id,'date'=>'2026-10-17','status'=>'Present','check_in'=>'22:00','check_out'=>'06:00']);
  $data=$attendanceController->index($request(['user_id'=>2]))->getData(true);hrCheck($data['total']===1&&$data['data'][0]['user_id']===2,'LOB employee filter narrows attendance');
  hrCheck($data['data'][0]['total_time']==='8h 00m','Overnight duration unchanged');
  $blocked=$attendanceController->index($request(['user_id'=>3]))->getData(true);hrCheck($blocked['total']===0,'Attendance employee filter stays within LOB');
  try{$attendanceController->index($request(['date'=>'invalid-date']));throw new RuntimeException('Expected validation');}catch(ValidationException $e){}
  try{$attendanceController->exportSummary($request(['month'=>'2026-99']));throw new RuntimeException('Expected month validation');}catch(ValidationException $e){}
  // Direct-manager leave decisions retain LOB boundaries and existing balances.
  DB::table('users')->where('id',2)->update(['manager_id'=>1]);
  $managerList=$leaveController->index($request(['team'=>'direct','status'=>'Pending']))->getData(true);
  hrCheck($managerList['total']>0 && $managerList['data'][0]['can_review']===true,'Direct manager receives review authority');
  $leaveController->updateStatus($request(['status'=>'Approved']),Leave::findOrFail(6));
  hrCheck(DB::table('leaves')->where('id',6)->value('approved_by')===1,'Manager decision records approver');
  DB::table('users')->where('id',2)->update(['manager_id'=>3]);
  try{$leaveController->updateStatus($request(['status'=>'Approved']),Leave::findOrFail(7));throw new RuntimeException('Expected reassignment denial');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){hrCheck($e->getStatusCode()===403,'Former manager loses review access');}
  DB::table('users')->where('id',2)->update(['manager_id'=>1]);
  $searched=$leaveController->index($request(['search'=>'E2','from_date'=>'2026-10-18','to_date'=>'2026-10-18','history'=>1]))->getData(true);
  hrCheck($searched['total']===1 && (int)$searched['summary']['requested_days']===2,'Employee ID search and overlapping date filters');
  $export=$leaveController->export($request(['user_id'=>2,'history'=>1]));ob_start();$export->sendContent();$csv=ob_get_clean();hrCheck(str_contains($csv,'Employee 2')&&!str_contains($csv,'Employee 3'),'Leave export uses report visibility and filters');
  $diagnostics=$attendanceController->index($request(['user_id'=>2,'per_page'=>1]))->getData(true);
  hrCheck((int)$diagnostics['summary']['completed_minutes']===480,'Full-result attendance time summary is independent of page size');

  $corrections=new AttendanceCorrectionController;
  DB::table('attendances')->insert(['id'=>4,'user_id'=>2,'date'=>'2026-10-17','check_in'=>'08:00:00','check_out'=>'17:00:00','status'=>'Present']);
  $viewer->id=2;
  $proposal=['attendance_id'=>4,'date'=>'2026-10-17','check_in_time'=>'2026-10-17T09:00','check_out_time'=>'2026-10-17T18:00','attendance_status'=>'Present','reason'=>'Clock times need correction','notes'=>'Corrected notes'];
  try{$corrections->store($request(array_merge($proposal,['check_in_time'=>'2026-10-16T09:00'])));throw new RuntimeException('Expected date denial');}catch(ValidationException $e){}
  $created=$corrections->store($request($proposal))->getData(true)['data'];
  hrCheck($created['status']==='Pending'&&DB::table('attendances')->where('id',4)->value('check_in')==='08:00:00','Pending correction does not modify attendance');
  hrCheck($created['check_in_time']==='2026-10-17T09:00','Correction dates serialize for form fields');
  try{$corrections->store($request($proposal));throw new RuntimeException('Expected duplicate denial');}catch(ValidationException $e){}
  $staleCorrection=AttendanceCorrection::findOrFail($created['id']);
  try{$corrections->review($request(['status'=>'Approved']),$staleCorrection);throw new RuntimeException('Expected self-review denial');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){hrCheck($e->getStatusCode()===403,'Employee cannot review their own request');}
  $viewer->id=1;
  $reviewInbox=$corrections->index($request(['scope'=>'review']))->getData(true);hrCheck($reviewInbox['total']===1&&$reviewInbox['data'][0]['can_review']===true,'Manager correction inbox');
  $approved=$corrections->review($request(['status'=>'Approved','reviewer_comment'=>'Verified']),$staleCorrection);
  hrCheck($approved->getStatusCode()===200&&DB::table('attendances')->where('id',4)->value('check_in')==='09:00:00','Approved correction applies proposed time');
  hrCheck(DB::table('attendance_corrections')->where('id',$created['id'])->value('reviewed_by')===1,'Reviewer audit metadata');
  hrCheck($corrections->review($request(['status'=>'Approved']),$staleCorrection)->getStatusCode()===422,'Correction cannot be processed twice');
  $viewer->id=2;
  $again=$corrections->store($request($proposal))->getData(true)['data'];
  DB::table('attendances')->where('id',4)->update(['notes'=>'Changed after submission']);
  $viewer->id=1;
  hrCheck($corrections->review($request(['status'=>'Approved']),AttendanceCorrection::findOrFail($again['id']))->getStatusCode()===409,'Stale correction cannot overwrite newer attendance');
  $corrections->review($request(['status'=>'Rejected']),AttendanceCorrection::findOrFail($again['id']));
  hrCheck(DB::table('attendances')->where('id',4)->value('notes')==='Changed after submission','Rejection does not modify attendance');
  $viewer->id=2;
  $missing=$corrections->store($request(['date'=>'2026-10-18','attendance_status'=>'Present','reason'=>'Missing day']))->getData(true)['data'];
  $viewer->id=1;
  $corrections->review($request(['status'=>'Approved']),AttendanceCorrection::findOrFail($missing['id']));
  hrCheck(DB::table('attendances')->where('user_id',2)->where('date','2026-10-18')->count()===1,'Approved missing-day request creates one record');
  $viewer->id=2;
  $rollback=$corrections->store($request($proposal))->getData(true)['data'];
  $viewer->id=1;
  DB::statement("CREATE TRIGGER hr_fixture_fail BEFORE UPDATE ON attendances FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Fixture failure'");
  try {
      try{$corrections->review($request(['status'=>'Approved']),AttendanceCorrection::findOrFail($rollback['id']));throw new RuntimeException('Expected correction write failure');}catch(\Illuminate\Database\QueryException $e){}
      hrCheck(DB::table('attendance_corrections')->where('id',$rollback['id'])->value('status')==='Pending','Attendance write failure leaves correction pending');
      hrCheck(DB::table('attendances')->where('id',4)->value('notes')==='Changed after submission','Failed approval does not partially change attendance');
  } finally { DB::statement('DROP TRIGGER hr_fixture_fail'); }
  $viewer->id=3;
  hrCheck($corrections->index($request())->getData(true)['total']===0,'Unrelated employee cannot see corrections');
  // A reporting assignment cannot bypass the established LOB boundary.
  DB::table('users')->where('id',3)->update(['manager_id'=>1]);
  $viewer->id=3;
  $outside=$corrections->store($request(['date'=>'2026-10-19','attendance_status'=>'Present','reason'=>'Outside team request']))->getData(true)['data'];
  $viewer->id=1;
  try{$corrections->review($request(['status'=>'Approved']),AttendanceCorrection::findOrFail($outside['id']));throw new RuntimeException('Expected LOB denial');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){hrCheck($e->getStatusCode()===403,'Manager review stays within LOB');}
  // An employee cannot submit a correction for somebody else's attendance.
  $viewer->id=3;
  try{$corrections->store($request($proposal));throw new RuntimeException('Expected owner denial');}catch(\Illuminate\Database\Eloquent\ModelNotFoundException $e){}
  $viewer->id=1;
  $viewer->global=true;
  DB::table('attendances')->where('id',2)->update(['deleted_at'=>now()]);
  $attendanceController->bulkAction($request(['action'=>'force-delete','ids'=>[2]]));hrCheck(!DB::table('attendances')->where('id',2)->exists(),'Bulk permanent deletion includes trashed records');
  $filteredExport=$attendanceController->exportFiltered($request(['user_id'=>3,'start_date'=>'2026-10-17','end_date'=>'2026-10-17']));ob_start();$filteredExport->sendContent();$filteredCsv=ob_get_clean();hrCheck(str_contains($filteredCsv,'Employee 3')&&!str_contains($filteredCsv,'Employee 1'),'Filtered attendance export preserves request identity and filters');
  $summary=$attendanceController->exportSummary($request(['month'=>'2026-10','user_id'=>3]));ob_start();$summary->sendContent();$csv=ob_get_clean();hrCheck(str_contains($csv,'Employee 3')&&!str_contains($csv,'Employee 1'),'Export respects selected employee');
  $roster=new \App\Modules\Users\Controllers\RosterController;
  $viewer->id=1; $viewer->global=false;
  DB::table('users')->where('id',1)->update(['manager_id'=>2]);
  $tmp=tempnam(sys_get_temp_dir(),'roster_csv_');
  $import=function(string $csv,bool $commit=false,string $hash='')use($request,$tmp,$roster){
    file_put_contents($tmp,$csv);
    $r=$request(['commit'=>$commit?'1':'0','preview_hash'=>$hash]);
    $r->files->set('file',new \Illuminate\Http\UploadedFile($tmp,'roster.csv','text/csv',null,true));
    return $roster->import($r)->getData(true);
  };
  try {
    $csv="employee_id,date,day_type\nE1,2026-10-16,Week Off\nE2,2026-10-16,Working\n";
    hrCheck(count($import("user_id,date,day_type\n1,2026-10-16,Week Off\n")['data'])===1,'System user ID roster import supported');
    $preview=$import($csv);
    hrCheck(count($preview['data'])===2 && DB::table('employee_rosters')->count()===0,'Roster preview must not write');
    $import($csv,true,$preview['preview_hash']);
    hrCheck(DB::table('employee_rosters')->count()===2,'Roster commits all valid rows');
    $calendar=$attendanceController->index($request(['user_id'=>1,'per_page'=>-1,'start_date'=>'2026-10-01','end_date'=>'2026-10-31']))->getData(true);
    hrCheck(count($calendar['rosters'])===1 && $calendar['rosters'][0]['day_type']==='Week Off','Attendance calendar API includes scoped roster markers without requiring attendance');
    try{$import("employee_id,date,day_type\nE1,2026-10-16,Working\nE3,2026-10-16,Week Off\n");throw new RuntimeException('Expected roster LOB denial');}catch(ValidationException $e){}
    try{$import("employee_id,date,day_type\nE1,2026-02-30,Week Off\n");throw new RuntimeException('Expected invalid roster date');}catch(ValidationException $e){}
    try{$import($csv."E1,2026-10-16,Working\n");throw new RuntimeException('Expected duplicate row denial');}catch(ValidationException $e){}
    try{$import($csv,true,'wrong');throw new RuntimeException('Expected preview hash denial');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){hrCheck($e->getStatusCode()===422,'Preview hash required');}
    DB::table('attendances')->insert(['user_id'=>1,'date'=>'2026-10-16','check_in'=>'09:00','check_out'=>'10:00','status'=>'Present']);
    $claim=['date'=>'2026-10-16','reason'=>'Worked on rostered off day'];
    $roster->claim($request($claim));
    hrCheck(!DB::table('leave_balances')->where('leave_type','CO')->exists(),'Pending CO cannot credit balance');
    try{$roster->claim($request($claim));throw new RuntimeException('Expected duplicate CO denial');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){hrCheck($e->getStatusCode()===422,'Duplicate CO denied');}
    $id=DB::table('employee_rosters')->where('user_id',1)->value('id');
    try{$roster->review($request(['status'=>'Approved']),$id);throw new RuntimeException('Expected self review denial');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){hrCheck($e->getStatusCode()===403,'Self approval denied');}
    try{$import("employee_id,date,day_type\nE1,2026-10-16,Working\n");throw new RuntimeException('Expected protected roster denial');}catch(ValidationException $e){}
    $viewer->id=2;
    $roster->review($request(['status'=>'Approved','comment'=>'Verified']),$id);
    hrCheck((float)DB::table('leave_balances')->where('user_id',1)->where('leave_type','CO')->value('total_leaves')===1.0,'Manager approval credits exactly 1 CO');
    try{$roster->review($request(['status'=>'Approved']),$id);throw new RuntimeException('Expected repeated CO review denial');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){hrCheck($e->getStatusCode()===422,'Repeated approval denied');}
    $preview=$import($csv); $import($csv,true,$preview['preview_hash']);
    hrCheck(DB::table('employee_rosters')->where('id',$id)->value('co_status')==='Approved','Reimport cannot reset CO status');
    $viewer->id=1;
    try{$roster->claim($request($claim));throw new RuntimeException('Expected credited date denial');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){hrCheck($e->getStatusCode()===422,'Credited day cannot be reclaimed');}
    $visible=$roster->index($request(['start_date'=>'2026-10-01','end_date'=>'2026-10-31','user_id'=>3]))->getData(true);
    hrCheck(count($visible['data'])===0,'Roster markers preserve LOB scoping');
    // A failed credit must roll back the review and keep the request pending.
    DB::table('employee_rosters')->insert(['user_id'=>1,'date'=>'2026-10-15','day_type'=>'Week Off','co_status'=>'Pending','co_reason'=>'Rollback test']);
    DB::table('attendances')->insert(['user_id'=>1,'date'=>'2026-10-15','check_in'=>'09:00','check_out'=>'09:01','status'=>'Present']);
    $rollbackId=DB::table('employee_rosters')->where('date','2026-10-15')->value('id');
    $viewer->id=2;
    DB::unprepared("CREATE TRIGGER co_credit_failure BEFORE UPDATE ON leave_balances FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Synthetic credit failure'");
    try {
      try{$roster->review($request(['status'=>'Approved']),$rollbackId);throw new RuntimeException('Expected credit failure');}catch(\Illuminate\Database\QueryException $e){}
      hrCheck(DB::table('employee_rosters')->where('id',$rollbackId)->value('co_status')==='Pending','Failed credit leaves request pending');
      hrCheck((float)DB::table('leave_balances')->where('user_id',1)->where('leave_type','CO')->value('total_leaves')===1.0,'Failed review cannot alter CO balance');
    } finally { DB::unprepared('DROP TRIGGER co_credit_failure'); }
    $roster->review($request(['status'=>'Rejected','comment'=>'Not eligible']),$rollbackId);
    hrCheck(DB::table('roster_events')->where('roster_id',$rollbackId)->where('action','Rejected')->exists(),'CO decisions have audit history');
    // Missing checkout is not evidence of completed work.
    DB::table('employee_rosters')->insert(['user_id'=>1,'date'=>'2026-10-14','day_type'=>'Week Off']);
    DB::table('attendances')->where('user_id',1)->where('date','2026-10-14')->delete();
    DB::table('attendances')->insert(['user_id'=>1,'date'=>'2026-10-14','check_in'=>'09:00','status'=>'Present']);
    $viewer->id=1;
    try{$roster->claim($request(['date'=>'2026-10-14','reason'=>'Incomplete']));throw new RuntimeException('Expected incomplete evidence denial');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){hrCheck($e->getStatusCode()===422,'Incomplete attendance cannot earn CO');}
    $viewer->global=true;
  } finally {unlink($tmp);}
  echo "PASS: leave filtering/pagination, inclusive balances, repeated approval, bulk approval, transaction rollback, existing approval/LOB rules, attendance filters, overnight duration, date validation, trashed bulk action employee export, direct-manager reviews, correction proposals, self-review denial, stale corrections, missing attendance, roster previews/import validation, CO approvals, duplicate-credit protection, credit rollback and audit metadata.\n";
 });
 // Real model events verify automatic CO across the same save path used by checkout.
 config(['audit.enabled'=>false]); app(\Spatie\Activitylog\ActivityLogStatus::class)->disable();
 $autoDate='2026-10-12';
 DB::table('employee_rosters')->insert(['user_id'=>2,'date'=>$autoDate,'day_type'=>'Week Off']);
 $session=Attendance::create(['user_id'=>2,'date'=>$autoDate,'check_in'=>'09:00','status'=>'Present']);
 hrCheck(DB::table('employee_rosters')->where('user_id',2)->where('date',$autoDate)->value('co_status')===null,'Checkin alone must not request CO');
 $session->update(['check_out'=>'09:01']);
 $autoRoster=DB::table('employee_rosters')->where('user_id',2)->where('date',$autoDate)->first();
 hrCheck($autoRoster->co_status==='Pending','Checkout automatically creates Pending CO via model event');
 hrCheck(!DB::table('leave_balances')->where('user_id',2)->where('leave_type','CO')->exists(),'Automatic CO cannot credit before review');
 $session->update(['check_out'=>'09:02']);
 Attendance::create(['user_id'=>2,'date'=>$autoDate,'check_in'=>'10:00','check_out'=>'11:00','status'=>'Present']);
 hrCheck(DB::table('roster_events')->where('roster_id',$autoRoster->id)->where('action','Auto requested')->count()===1,'Edits and multiple sessions cannot duplicate automatic requests');
 DB::table('employee_rosters')->where('id',$autoRoster->id)->update(['co_status'=>'Rejected']);
 $session->update(['check_out'=>'09:03']);
 hrCheck(DB::table('employee_rosters')->where('id',$autoRoster->id)->value('co_status')==='Rejected','Attendance edits cannot reopen rejected CO');
 DB::table('employee_rosters')->insert(['user_id'=>2,'date'=>'2026-10-11','day_type'=>'Week Off']);
 $synthetic=Attendance::create(['user_id'=>2,'date'=>'2026-10-11','check_in'=>'09:00','status'=>'Present']);
 $synthetic->suppressAutomaticCoRequest=true; $synthetic->update(['check_out'=>'23:59:59']);
 hrCheck(DB::table('employee_rosters')->where('user_id',2)->where('date','2026-10-11')->value('co_status')===null,'Synthetic rollover cannot request CO');
 DB::table('employee_rosters')->insert(['user_id'=>2,'date'=>'2026-10-10','day_type'=>'Working']);
 Attendance::create(['user_id'=>2,'date'=>'2026-10-10','check_in'=>'09:00','check_out'=>'10:00','status'=>'Present']);
 hrCheck(DB::table('employee_rosters')->where('user_id',2)->where('date','2026-10-10')->value('co_status')===null,'Working days cannot automatically request CO');
 DB::table('employee_rosters')->insert(['user_id'=>2,'date'=>'2026-10-09','day_type'=>'Week Off']);
 Attendance::create(['user_id'=>2,'date'=>'2026-10-09','check_in'=>'23:00','check_out'=>'01:00','status'=>'Present']);
 hrCheck(DB::table('employee_rosters')->where('user_id',2)->where('date','2026-10-09')->value('co_status')==='Pending','Completed overnight attendance requests CO');
 echo "PASS: automatic CO model hooks, checkin-only denial, checkout request, no premature balance credit, duplicate sessions, rejected decisions, rollover suppression, working days and overnight attendance.\n";
}finally{DB::setDefaultConnection($original);DB::purge('hr_test');$pdo->exec("DROP DATABASE `{$name}`");if($originalUser)auth()->setUser($originalUser);else auth()->forgetUser();}
