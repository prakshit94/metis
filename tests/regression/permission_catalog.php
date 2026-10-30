<?php

require __DIR__.'/bootstrap.php';

use App\Modules\Users\Models\Permission;
use App\Services\PermissionCatalogSync;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

foreach ([
    '2025_01_01_000002_create_permissions_table.php', '2025_01_01_000002a_create_roles_table.php',
    '2025_01_01_000002b_create_model_has_permissions_table.php', '2025_01_01_000002c_create_model_has_roles_table.php',
    '2025_01_01_000002d_create_role_has_permissions_table.php',
] as $file) (require dirname(__DIR__,2).'/database/migrations/'.$file)->up();
$role=DB::table('roles')->insertGetId(['name'=>'Custom scoped role','guard_name'=>'web','team_id'=>7]);
$source=Permission::findOrCreate('settings-edit','web');
$custom=Permission::findOrCreate('fixture.custom','web');
DB::table('role_has_permissions')->insert(['role_id'=>$role,'permission_id'=>$source->id]);
DB::table('role_has_permissions')->insert(['role_id'=>$role,'permission_id'=>$custom->id]);
foreach ([null,7,9] as $team) DB::table('model_has_permissions')->insert(['team_id'=>$team,'model_type'=>'App\\Modules\\Users\\Models\\User','model_id'=>88,'permission_id'=>$source->id]);
$deleted=Permission::findOrCreate('files-download','web');$deleted->delete();
$migration=require dirname(__DIR__,2).'/database/migrations/2026_10_28_140000_complete_feature_permissions.php';
$migration->up();
check(Permission::where('name','security-mail-edit')->exists(), 'migration registers missing feature permissions');
$action=Permission::findByName('security-mail-edit','web');
check(DB::table('role_has_permissions')->where('role_id',$role)->where('permission_id',$action->id)->exists(), 'new action preserves the old role grant');
$teams=DB::table('model_has_permissions')->where('model_id',88)->where('permission_id',$action->id)->pluck('team_id')->all();sort($teams);
check($teams===[null,7,9], 'direct user grants preserve every null/global and individual team scope');
check(DB::table('role_has_permissions')->where('role_id',$role)->where('permission_id',$custom->id)->exists(), 'custom permission and assignment remain untouched');
check(!Permission::where('name','files-download')->exists() && Permission::withTrashed()->where('name','files-download')->count()===1, 'catalog refresh does not resurrect a soft-deleted permission');
$before=DB::table('model_has_permissions')->count();$roleBefore=DB::table('role_has_permissions')->count();
$migration->up();
check(DB::table('model_has_permissions')->count()===$before && DB::table('role_has_permissions')->count()===$roleBefore, 'repeated compatibility sync does not duplicate grants including nullable team rows');
DB::table('role_has_permissions')->where('role_id',$role)->where('permission_id',$action->id)->delete();
app(\Database\Seeders\FeaturePermissionsSeeder::class)->run();
check(!DB::table('role_has_permissions')->where('role_id',$role)->where('permission_id',$action->id)->exists(), 'permission-only seeder preserves an explicit administrator revocation');
check(!Schema::hasTable('users') && DB::table('roles')->count()===1, 'permission-only update creates no users and resets no roles');
$migration->down();
check(Permission::where('name','security-mail-edit')->exists(), 'rollback preserves administrator permission assignments');
echo "Permission catalog checks complete.\n";

$teamView = Permission::findByName('team-view', 'web');
DB::table('role_has_permissions')->insertOrIgnore(['role_id'=>$role, 'permission_id'=>$teamView->id]);
DB::table('role_has_permissions')->where('role_id',$role)->where('permission_id',$action->id)->delete();
app(PermissionCatalogSync::class)->run(true, ['office-view','office-create','office-edit','office-delete']);
$officeView = Permission::findByName('office-view','web');
check(DB::table('role_has_permissions')->where('role_id',$role)->where('permission_id',$officeView->id)->exists(), 'office compatibility migration copies corresponding team grants');
check(!DB::table('role_has_permissions')->where('role_id',$role)->where('permission_id',$action->id)->exists(), 'office-only compatibility migration preserves unrelated manual revocations');

// Explicit post-import repair differs from an ordinary catalog-only refresh.
$productView = Permission::findByName('product-view', 'web');
DB::table('role_has_permissions')->insertOrIgnore(['role_id'=>$role,'permission_id'=>$productView->id]);
foreach ([null,7,9] as $team) DB::table('model_has_permissions')->insert(['team_id'=>$team,'model_type'=>'App\\Modules\\Users\\Models\\User','model_id'=>77,'permission_id'=>$productView->id]);
$customBefore=DB::table('role_has_permissions')->where('permission_id',$custom->id)->count();
app(\Database\Seeders\LegacyPermissionGrantsSeeder::class)->run();
$productExport=Permission::findByName('product-export','web');
check(DB::table('role_has_permissions')->where('role_id',$role)->where('permission_id',$productExport->id)->exists(),'explicit legacy repair restores product-export for existing product-view roles');
$productTeams=DB::table('model_has_permissions')->where('model_id',77)->where('permission_id',$productExport->id)->pluck('team_id')->all();sort($productTeams);
check($productTeams===[null,7,9],'explicit legacy repair preserves direct product grant team scopes');
$writeIds=Permission::whereIn('name',['product-create','product-edit','product-delete','product-restore','product-permanent-delete','product-import'])->pluck('id');
check(!DB::table('role_has_permissions')->where('role_id',$role)->whereIn('permission_id',$writeIds)->exists(),'product-view compatibility does not grant product write, import or destructive actions');
check(DB::table('role_has_permissions')->where('permission_id',$custom->id)->count()===$customBefore && DB::table('roles')->count()===1 && !Schema::hasTable('users'),'explicit legacy repair preserves custom grants and never resets roles or users');
$repairCounts=[DB::table('role_has_permissions')->count(),DB::table('model_has_permissions')->count()];
app(\Database\Seeders\LegacyPermissionGrantsSeeder::class)->run();
check($repairCounts===[DB::table('role_has_permissions')->count(),DB::table('model_has_permissions')->count()],'explicit legacy repair is idempotent for role and nullable team grants');
