<?php
require __DIR__.'/bootstrap.php';

use App\Modules\Users\Models\User;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\Permission;
use App\Services\TeamContext;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Illuminate\Session\ArraySessionHandler;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Exceptions\UnauthorizedException;

foreach (['2025_01_01_000002_create_permissions_table.php','2025_01_01_000002a_create_roles_table.php',
    '2025_01_01_000002b_create_model_has_permissions_table.php','2025_01_01_000002c_create_model_has_roles_table.php',
    '2025_01_01_000002d_create_role_has_permissions_table.php'] as $file) (require database_path('migrations/'.$file))->up();
Schema::create('users',function(Blueprint $table){$table->id();$table->string('name');$table->string('email');$table->string('password');$table->boolean('is_active')->default(true);$table->timestamps();$table->softDeletes();});
$member=User::create(['name'=>'Scoped test','email'=>'scope@example.invalid','password'=>'Example123!']);
$admin=User::create(['name'=>'Global test','email'=>'global@example.invalid','password'=>'Example123!']);
$limited=User::create(['name'=>'Limited test','email'=>'limited@example.invalid','password'=>'Example123!']);
$role=Role::findOrCreate('Scoped dashboard','web');$basic=Role::findOrCreate('Limited','web');$root=Role::findOrCreate('Super Admin','web');
$role->givePermissionTo(Permission::findOrCreate('dashboard-view','web'));
setPermissionsTeamId(1);$member->assignRole($role);$limited->assignRole($basic);
setPermissionsTeamId(null);$admin->assignRole($root);
function scopeRequest(?User $user, string $path='/', string $routeName='dashboard', bool $bearer=false, bool $json=false): Request {
    global $app;
    auth()->forgetGuards();auth()->shouldUse('web');
    if($user) auth()->guard('web')->setUser($user);
    $request=Request::create($path,'GET',[],[],[],['HTTP_ACCEPT'=>$json?'application/json':'text/html']+($bearer?['HTTP_AUTHORIZATION'=>'Bearer fixture-only']:[]));
    $request->setUserResolver(fn()=>$user);
    $session=new Store('redirect-fixture',new ArraySessionHandler(120));$session->start();$session->put('team_id',2);
    $request->setLaravelSession($session);$app->instance('session.store',$session);$app->instance('request',$request);
    $route=new Illuminate\Routing\Route('GET',ltrim($path,'/'),fn()=>null);$route->name($routeName);$request->setRouteResolver(fn()=>$route);
    return $request;
}
function permissionResponse(Request $request,string $permission='dashboard-view|warehouse-dashboard-view') {
    try {return app(TeamContext::class)->handle($request,fn($r)=>app(PermissionMiddleware::class)->handle($r,fn()=>response('allowed'),$permission));}
    catch(UnauthorizedException $error){return app(Illuminate\Contracts\Debug\ExceptionHandler::class)->render($request,$error);}
}
setPermissionsTeamId(2);$request=scopeRequest($member->fresh());$response=permissionResponse($request);
check($response->getStatusCode()===200 && getPermissionsTeamId()===1 && $request->session()->get('team_id')===1,'stale browser state is repaired before dashboard permission check');
$request=scopeRequest($member->fresh(), '/', 'dashboard', true);$response=permissionResponse($request);
check($response->getStatusCode()===200 && getPermissionsTeamId()===1 && $request->session()->get('team_id')===2,'bearer identity uses its own scope and never rewrites browser context');
$request=scopeRequest($admin->fresh());$response=permissionResponse($request);
check($response->getStatusCode()===200 && getPermissionsTeamId()===null && !$request->session()->has('team_id'),'global administrator ignores and removes leftover scoped session context');
$request=scopeRequest($limited->fresh());$response=permissionResponse($request);
check($response->getStatusCode()===302 && str_ends_with($response->headers->get('Location'),'/admin/security'),'user lacking dashboard permission receives a different owner-accessible destination');
$request=scopeRequest($member->fresh(),'/admin/users','users');$response=permissionResponse($request,'user-view');
check($response->getStatusCode()===302 && $response->headers->get('Location')===route('dashboard'),'denied page retains dashboard fallback when dashboard access exists');
$request=scopeRequest($member->fresh());check(permissionResponse($request)->getStatusCode()===200,'dashboard fallback terminates with allowed response rather than redirecting to itself');
$request=scopeRequest($limited->fresh(),'/admin/users','users');$response=permissionResponse($request,'user-view');
check($response->getStatusCode()===302 && str_ends_with($response->headers->get('Location'),'/admin/security'),'denied page with no dashboard grant falls back to Security');
$request=scopeRequest($limited->fresh(),'/admin/security','security');$response=permissionResponse($request,'fixture-missing');
check($response->getStatusCode()===403 && !$response->headers->has('Location'),'unexpected denial on fallback page renders terminal 403, preventing any second redirect loop');
$request=scopeRequest($limited->fresh(),'/api/fixture','api.fixture',false,true);$response=permissionResponse($request);
check($response->getStatusCode()===403 && !$response->headers->has('Location'),'API permission denial stays JSON 403 instead of browser redirects');
$request=scopeRequest(null,'/admin/login','login');app(TeamContext::class)->handle($request,fn()=>response('login'));
check(getPermissionsTeamId()===null && !$request->session()->has('team_id'),'guest request clears leftover identity context');
DB::table('model_has_roles')->where('model_id',$member->id)->where('model_type',User::class)->update(['team_id'=>3]);
$request=scopeRequest($member->fresh());$request->session()->put('team_id',1);$response=permissionResponse($request);
check($response->getStatusCode()===200 && $request->session()->get('team_id')===3,'role reassignment repairs an existing browser context without editing real sessions or granting permissions');
