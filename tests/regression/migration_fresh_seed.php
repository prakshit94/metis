<?php
// This bootstrap creates and deletes only its own randomly named test schema.
require __DIR__.'/bootstrap.php';

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

check(!glob(database_path('schema/*.sql')), 'no SQL schema dump can bypass PHP migrations');
check(!is_dir(database_path('schema/tables')), 'generated per-table SQL definitions are removed');
$files=glob(database_path('migrations/*.php'));
sort($files);
$before=[];
foreach($files as $file) $before[basename($file)]=hash_file('sha256',$file);
$status=Artisan::call('migrate:fresh', ['--database'=>'regression','--seed'=>true,'--force'=>true]);
if($status!==0) fwrite(STDERR,Artisan::output());
check($status===0, 'migrate:fresh --seed completes on a disposable database');
$output=Artisan::output();
check(!str_contains($output,'Loading stored database schemas'), 'Artisan does not load a SQL schema baseline');
$expected=array_map(fn($file)=>pathinfo($file,PATHINFO_FILENAME),$files);
$actual=DB::table('migrations')->orderBy('migration')->pluck('migration')->all();
check($actual===$expected, 'every original PHP migration is recorded exactly once');
// Temporarily project only this disposable database back to the dated baseline.
$promotionUpgrade=require database_path('migrations/2026_11_01_000001_harden_promotion_rewards.php');
check(\Illuminate\Support\Facades\Schema::hasTable('promotion_locks'), 'fresh migration includes the promotion activation lock');
$promotionUpgrade->down();
$tables=DB::connection()->getSchemaBuilder()->getTableListing(DB::connection()->getDatabaseName(),false);
$reference=json_decode(file_get_contents(database_path('transitions/metis-20261029/audited-target-schema.json')),true,512,JSON_THROW_ON_ERROR);
sort($tables);$names=array_keys($reference);sort($names);
check($tables===$names, 'all historical baseline tables are preserved by the forward upgrade');
$normalize=fn($sql)=>preg_replace('/ CHARACTER SET utf8mb4(?= COLLATE utf8mb4_)/','',preg_replace('/ AUTO_INCREMENT=\d+/','',trim($sql)));
foreach($tables as $table){
 $row=(array)DB::selectOne('SHOW CREATE TABLE `'.$table.'`');
 if($normalize($row['Create Table'])!==$normalize($reference[$table])) throw new RuntimeException('Schema differs: '.$table);
}
check(true, 'columns, types, indexes and foreign keys match the audited project schema');
$promotionUpgrade->up();
check(count(DB::connection()->getSchemaBuilder()->getTableListing(DB::connection()->getDatabaseName(),false))===count($reference)+1, 'current schema adds only the activation-lock table to the historical baseline');
foreach (['coupons'=>['recipient_party_id'], 'referral_program_milestones'=>['retired_at'], 'referral_rewards'=>['referral_threshold','entitlement_key','recovery_due']] as $table=>$columns) {
    foreach ($columns as $column) check(\Illuminate\Support\Facades\Schema::hasColumn($table,$column), 'current schema contains '.$table.'.'.$column);
}
check(\Illuminate\Support\Facades\Schema::hasIndex('referral_rewards',['entitlement_key'],'unique'), 'reward entitlement uniqueness is installed');

check(DB::table('permissions')->count()===425 && DB::table('roles')->count()>0 && DB::table('teams')->count()>0, 'existing permission, role and team seeders complete');
check(DB::table('referral_program_milestones as m')->leftJoin('products as p','p.id','=','m.reward_value')->where('m.reward_type','product')->whereNull('p.id')->count()===0, 'fresh referral defaults never reference missing demo products');
check(DB::table('customer_accounts')->count()===0 && DB::table('orders')->count()===0, 'fresh seeding does not create storefront accounts or orders');
foreach($files as$file) if(hash_file('sha256',$file)!==$before[basename($file)]) throw new RuntimeException('Migration source changed');
check(true,'all original migration sources remain unchanged');
echo 'PHP fresh migration and seeder verification complete.'.PHP_EOL;
