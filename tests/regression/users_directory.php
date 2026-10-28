<?php

require __DIR__.'/bootstrap.php';

use App\Modules\Users\Models\User;
use App\Modules\Users\Models\Role;
use App\Modules\Users\Models\Permission;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::call('migrate', ['--force' => true]);
function directoryUser(string $name): User {
    return User::create(['name' => $name, 'first_name' => $name, 'middle_name' => 'Middle', 'last_name' => 'Last', 'email' => $name.'@example.invalid', 'password' => 'Example123!', 'is_active' => true]);
}
function directoryHttp(string $method, string $uri, string $token, array $body = []): array {
    global $app;
    auth()->forgetGuards(); auth()->shouldUse('web');
    $request = Request::create($uri, $method, $body, [], [], ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer '.$token]);
    $response = $app->make(Kernel::class)->handle($request);
    return [$response->getStatusCode(), json_decode($response->getContent(), true)];
}
$master = directoryUser('master');
$actor = directoryUser('operator');
$other = directoryUser('other');
$local = directoryUser('local');
$root = directoryUser('root');
$inactiveRoot = directoryUser('inactive-root');
$rootRole = Role::findOrCreate('Super Admin', 'web');
$memberRole = Role::findOrCreate('User', 'web');
$secondRole = Role::findOrCreate('Reviewer', 'web');
$operatorRole = Role::findOrCreate('Directory operator', 'web');
$permissions = ['user-view', 'user-edit', 'user-create', 'user-delete', 'user-restore', 'user-activate', 'user-permanent-delete', 'user-sync-roles', 'user-sync-permissions', 'audit-log-view'];
foreach ($permissions as $permission) $operatorRole->givePermissionTo(Permission::findOrCreate($permission, 'web'));
$teamA = DB::table('teams')->insertGetId(['name' => 'A', 'code' => 'GJ', 'is_active' => true]);
$teamB = DB::table('teams')->insertGetId(['name' => 'B', 'code' => 'RJ', 'is_active' => true]);
setPermissionsTeamId(null); $root->assignRole($rootRole); $inactiveRoot->assignRole($rootRole); $inactiveRoot->update(['is_active' => false]);
setPermissionsTeamId($teamA); $actor->assignRole($operatorRole); $local->assignRole([$memberRole, $secondRole]);
$local->givePermissionTo(Permission::findOrCreate('fixture-grant', 'web'));
setPermissionsTeamId($teamB); $other->assignRole($memberRole);
setPermissionsTeamId(null);
$actorToken = $actor->createToken('directory-fixture')->plainTextToken;
$rootToken = $root->createToken('directory-fixture')->plainTextToken;
DB::table('user_two_factor_settings')->insert(['user_id' => $local->id, 'confirmed_at' => now(), 'method' => 'authenticator', 'secret' => 'must-never-appear', 'recovery_hashes' => '["private-fixture"]', 'version' => 'fixture']);
[$status, $data] = directoryHttp('GET', '/api/users?include_summary=1', $actorToken);
check($status === 200 && count($data['data']) === 2, 'directory retains existing LOB list boundary');
$listed = collect($data['data'])->firstWhere('id', $local->id);
check($listed['two_factor_enabled'] === true && $listed['two_factor_method'] === 'authenticator', 'directory exposes only safe two-step status');
check(!str_contains(json_encode($data), 'must-never-appear') && !str_contains(json_encode($data), 'private-fixture'), 'directory never serializes factor secrets or recovery hashes');
foreach ([['GET', ''], ['PATCH', '', ['first_name' => 'intrusion']], ['DELETE', ''], ['PATCH', '/toggle-active'], ['PATCH', '/unsuspend'], ['POST', '/sync-roles', ['roles' => ['User']]], ['POST', '/sync-permissions', ['permissions' => ['fixture-grant']]], ['GET', '/login-history'], ['POST', '/update-photo']] as $case) {
    [$status] = directoryHttp($case[0], '/api/users/'.$other->id.$case[1], $actorToken, $case[2] ?? []);
    check($status === 404, 'cross-LOB '.$case[0].' '.$case[1].' is blocked');
}
[$status] = directoryHttp('POST', '/api/users/bulk-action', $actorToken, ['action' => 'deactivate', 'ids' => [$local->id, $other->id]]);
check($status === 404 && $local->fresh()->is_active && $other->fresh()->is_active, 'mixed-LOB bulk action is rejected before any mutation');
[$status] = directoryHttp('PATCH', '/api/users/'.$local->id, $actorToken, ['first_name' => 'Renamed']);
check($status === 200 && $local->fresh()->name === 'Renamed Middle Last', 'partial name update preserves omitted name pieces');
check(DB::table('model_has_roles')->where('model_id', $local->id)->count() === 2 && DB::table('model_has_permissions')->where('model_id', $local->id)->count() === 1, 'ordinary profile save preserves every role and direct permission');
[$status] = directoryHttp('PATCH', '/api/users/'.$local->id, $actorToken, ['team_id' => $teamB, 'roles' => ['User']]);
check($status === 403 && DB::table('model_has_roles')->where('model_id', $local->id)->where('team_id', $teamA)->exists(), 'scoped operator cannot move users outside their LOB');
[$status] = directoryHttp('PATCH', '/api/users/'.$local->id, $actorToken, ['manager_id' => $local->id]);
check($status === 422, 'self-manager cycle is rejected');
$actor->update(['manager_id' => $local->id]);
[$status] = directoryHttp('PATCH', '/api/users/'.$local->id, $actorToken, ['manager_id' => $actor->id]);
check($status === 422, 'indirect reporting cycle is rejected');
[$status] = directoryHttp('PATCH', '/api/users/'.$local->id, $rootToken, ['roles' => ['User', 'Reviewer'], 'team_id' => $teamB]);
check($status === 200 && DB::table('model_has_roles')->where('model_id', $local->id)->where('team_id', $teamB)->count() === 2, 'authorized LOB move retains selected multiple roles');
check(DB::table('model_has_permissions')->where('model_id', $local->id)->where('team_id', $teamB)->count() === 1 && getPermissionsTeamId() === null, 'LOB move retains direct grants and restores caller team context');
[$status] = directoryHttp('POST', '/api/users/'.$root->id.'/sync-roles', $actorToken, ['roles' => ['User']]);
check(in_array($status, [403,404], true), 'lower-privilege operator cannot demote a Super Admin');
foreach ([['PATCH', '/api/users/'.$root->id, ['roles' => ['User']]], ['DELETE', '/api/users/'.$root->id, []], ['POST', '/api/users/bulk-action', ['action' => 'delete', 'ids' => [$root->id]]], ['POST', '/api/users/bulk-action', ['action' => 'deactivate', 'ids' => [$root->id]]]] as [$method,$uri,$payload]) {
    [$status] = directoryHttp($method,$uri,$rootToken,$payload);
    check($status === 403 && $root->fresh()?->is_active, 'last active Super Admin protected for '.$method.' '.$uri);
}
// A global operator with permanent-delete permission needs no unrelated user-delete permission.
$deleter=directoryUser('deleter');$deleter->givePermissionTo(Permission::findOrCreate('user-permanent-delete','web'));
$deleterToken=$deleter->createToken('directory-fixture')->plainTextToken;
$deleted=directoryUser('deleted');$deleted->assignRole($memberRole);$deleted->delete();
[$status] = directoryHttp('DELETE','/api/users/'.$deleted->id.'/force',$deleterToken);
check($status===200 && !User::withTrashed()->find($deleted->id), 'permanent delete uses its documented operation permission');
$bulkDeleted=directoryUser('bulk-deleted');$bulkDeleted->assignRole($memberRole);$bulkDeleted->delete();
[$status] = directoryHttp('POST','/api/users/bulk-action',$deleterToken,['action'=>'force-delete','ids'=>[$bulkDeleted->id]]);
check($status===200 && !DB::table('model_has_roles')->where('model_id',$bulkDeleted->id)->exists(), 'bulk permanent deletion runs relationship cleanup hooks');
[$status] = directoryHttp('POST','/api/users/bulk-action',$rootToken,['action'=>'activate','ids'=>[$other->id,$other->id]]);
check($status===422, 'duplicate bulk IDs are rejected');
$local->createToken('old-password');$local->setRememberToken('fixture-old-cookie');$local->saveQuietly();
[$status] = directoryHttp('PATCH','/api/users/'.$local->id,$rootToken,['password'=>'NewExample123!','password_confirmation'=>'NewExample123!']);
check($status===200 && $local->tokens()->count()===0 && $local->fresh()->remember_token===null && $local->fresh()->password_changed_at!==null, 'password reset revokes old tokens and remember cookies and records timestamp');
$plain=directoryUser('plain');$plainToken=$plain->createToken('directory-fixture')->plainTextToken;
[$status] = directoryHttp('GET','/api/users',$plainToken);
check($status===403, 'directory API retains view permission requirement');
foreach (['enabled' => 1, 'disabled' => 1, 'authenticator' => 1, 'email' => 0] as $filter => $expected) {
    [$status, $data] = directoryHttp('GET', '/api/users?team_id='.$teamB.'&two_factor='.$filter, $rootToken);
    $expected = $filter === 'disabled' ? 1 : $expected;
    check($status === 200 && $data['total'] === $expected, 'two-step status filter '.$filter.' uses confirmed enrollment only');
}
[$status, $data] = directoryHttp('GET', '/api/users?role=does-not-exist', $rootToken);
check($status === 200 && $data['total'] === 0, 'unknown role filters return empty directory instead of server error');
[$status] = directoryHttp('GET', '/api/users?search[]=bad', $rootToken);
check($status === 422, 'malformed search input is rejected cleanly');
$createdEmail = 'created-via-api@example.invalid';
[$status, $data] = directoryHttp('POST', '/api/users', $rootToken, ['first_name' => 'Created', 'email' => $createdEmail, 'password' => 'Example123!', 'password_confirmation' => 'Example123!', 'roles' => ['User'], 'team_id' => $teamB]);
$createdId = $data['data']['id'] ?? null;
check($status === 201 && $createdId && getPermissionsTeamId() === null, 'valid user creation assigns requested LOB and restores caller context');
[$status] = directoryHttp('DELETE', '/api/users/'.$createdId, $rootToken);
check($status === 200 && User::withTrashed()->find($createdId)->trashed(), 'ordinary soft delete remains functional');
[$status] = directoryHttp('PATCH', '/api/users/'.$createdId.'/restore', $rootToken);
check($status === 200 && !User::find($createdId)->trashed(), 'ordinary restore remains functional');
[$status, $data] = directoryHttp('GET', '/api/users?include_summary=1', $rootToken);
$previewUsers = $data;
auth()->shouldUse('web'); auth()->guard('web')->setUser($root->fresh()); setPermissionsTeamId(null);
$html = view('users.index', ['teams' => \App\Modules\Users\Models\Team::all()])->render();
check(str_contains($html, 'My security &amp; 2-step verification') && str_contains($html, 'Two-step verification status'), 'complete Blade page renders security controls with existing layout');
$previewDir = '/tmp/metis-users-preview';
if (!is_dir($previewDir)) mkdir($previewDir, 0700, true);
file_put_contents($previewDir.'/index.html', str_replace('<body', '<body data-isolated-preview="true"', $html));
file_put_contents($previewDir.'/users.json', json_encode($previewUsers));
$logOptions = (new User)->getActivitylogOptions();
check(in_array('password', $logOptions->logExceptAttributes, true), 'user activity logging excludes password hashes');
[$status, $options] = directoryHttp('GET', '/api/users/options', $actorToken);
check($status === 200 && count($options['data']) === 1 && $options['data'][0]['id'] === $actor->id, 'minimal manager options retain the same LOB visibility');
check(!array_intersect(['email', 'password', 'phone', 'permissions', 'roles'], array_keys($options['data'][0])), 'manager options omit private profile fields and authorization payloads');
$oldEmail = $other->email;
$other->update(['email_verified_at' => now()]);
[$status] = directoryHttp('PATCH', '/api/users/'.$other->id, $rootToken, ['email' => 'changed@example.invalid']);
check($status === 200 && $other->fresh()->email_verified_at === null, 'changing email clears stale email verification status');
$other->createToken('expired-fixture', ['*'], now()->subMinute());
[$status, $data] = directoryHttp('GET', '/api/users/'.$other->id, $rootToken);
[$status, $listing] = directoryHttp('GET', '/api/users?search=changed@example.invalid', $rootToken);
check($status === 200 && $listing['data'][0]['is_online'] === false, 'expired mobile tokens do not mark the directory user online');
require __DIR__.'/users_two_factor_policy.php';
echo "Users directory regression checks complete.\n";
