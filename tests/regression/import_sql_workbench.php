<?php
// Synthetic, empty source data only; never imports real backup rows or touches live DB.
require __DIR__.'/bootstrap.php';
use Illuminate\Support\Facades\DB;
$folder=database_path('transitions/metis-20261029');
$stage='metis_regression_'.getmypid().'_'.bin2hex(random_bytes(3));
$pdo->exec('CREATE DATABASE `'.$stage.'`');
register_shutdown_function(fn()=>$pdo->exec('DROP DATABASE IF EXISTS `'.$stage.'`'));
$c=DB::connection();
$c->statement('SET FOREIGN_KEY_CHECKS=0');
foreach(json_decode(file_get_contents($folder.'/audited-target-schema.json'),true) as$ddl)$c->unprepared($ddl);
$c->statement('SET FOREIGN_KEY_CHECKS=1');
foreach(glob(database_path('migrations/*.php')) as$file)$c->table('migrations')->insert(['migration'=>pathinfo($file,PATHINFO_FILENAME),'batch'=>1]);
$c->table('users')->insert(['name'=>'Import sentinel','email'=>'import-sentinel@example.invalid','password'=>'synthetic-not-a-real-credential']);
$pdo->exec('USE `'.$stage.'`');$pdo->exec('SET FOREIGN_KEY_CHECKS=0');
$f=fopen('/home/user/dumps/metis120261029.sql','r');$ddl=null;$sourceTables=0;
while(($line=fgets($f))!==false){
 if(str_starts_with($line,'CREATE TABLE `')){$ddl=$line;continue;}
 if($ddl!==null){$ddl.=$line;if(str_starts_with($line,') ENGINE=')){$pdo->exec($ddl);$sourceTables++;$ddl=null;}}
}
fclose($f);$pdo->exec('SET FOREIGN_KEY_CHECKS=1');
check($sourceTables===109,'all backup table definitions parsed without loading private rows');
$original=file_get_contents($folder.'/import-data-full.sql');
$map=fn($s)=>str_replace(['`metis`',"'metis'",'metis_transition_backup_20261029'],['`'.$testDatabase.'`',"'".$testDatabase."'",$stage],$s);
$sql=$map($original);
function procedure(string$sql):string{
 $start=strpos($sql,'CREATE PROCEDURE ');$end=strpos($sql,"\nEND$$",$start===false?0:$start);
 if($start===false||$end===false)throw new RuntimeException('Procedure not found');
 return substr($sql,$start,$end+4-$start);
}
function callImport($c):void{
 $stmt=$c->getPdo()->prepare('CALL metis_replace_backup_20261029()');
 try{$stmt->execute();do{$stmt->fetchAll();}while($stmt->nextRowset());}finally{$stmt->closeCursor();}
}
function rejected($c,string$expected):void{
 try{callImport($c);}catch(Throwable$e){check(str_contains($e->getMessage(),$expected),'guard rejects '.$expected);return;}
 throw new RuntimeException('Guard did not reject '.$expected);
}
$c->statement("SET SESSION sql_safe_updates=1, time_zone='+05:30'");
$c->unprepared(procedure($sql));
rejected($c,'explicit confirmation');
$c->statement("SET @METIS_REPLACE_CONFIRMED='REPLACE ALL METIS DATA FROM 20261029'");
rejected($c,'Backup row count differs');
check($c->table('users')->count()===1,'incomplete staging fails before deleting existing data');
$settings=(array)$c->selectOne('SELECT @@SESSION.sql_safe_updates AS safe_updates, @@SESSION.time_zone AS tz, @@SESSION.foreign_key_checks AS fk');
check((int)$settings['safe_updates']===1 && $settings['tz']==='+05:30' && (int)$settings['fk']===1,'failure restores Workbench session settings');
$c->unprepared('DROP PROCEDURE metis_replace_backup_20261029');
// Only the test copy changes audited row-count expectations to the empty synthetic source.
$fixture=preg_replace('/(\(SELECT COUNT\(\*\) FROM `'.preg_quote($stage,'/').'`\.`[^`]+`\) <>) \d+/','$1 0',$sql,-1,$changed);
check($changed===109,'all audited source counts replaced only in the synthetic fixture');
$c->unprepared(procedure($fixture));
$last=$c->table('migrations')->orderByDesc('id')->first();
$c->statement('SET SESSION sql_safe_updates=0');$c->table('migrations')->where('id',$last->id)->delete();$c->statement('SET SESSION sql_safe_updates=1');
rejected($c,'Run all current PHP migrations');
$c->table('migrations')->insert((array)$last);
$c->unprepared('DROP PROCEDURE metis_replace_backup_20261029');
$rollbackFixture=str_replace('  DELETE FROM `users`;', "  DELETE FROM `users`;\n  SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Injected rollback test';",$fixture,$injected);
check($injected===1,'rollback fixture injects one error after deleting the sentinel user');
$c->unprepared(procedure($rollbackFixture));
rejected($c,'Injected rollback test');
check($c->table('users')->count()===1,'an error after deletion rolls back the existing user data');
$restored=(array)$c->selectOne('SELECT @@SESSION.sql_safe_updates AS safe_updates, @@SESSION.time_zone AS tz, @@SESSION.foreign_key_checks AS fk');
check((int)$restored['safe_updates']===1 && $restored['tz']==='+05:30' && (int)$restored['fk']===1,'transaction failure restores Workbench session settings');
$c->unprepared('DROP PROCEDURE metis_replace_backup_20261029');
$input=tempnam(sys_get_temp_dir(),'metis-import-fixture-');$output=tempnam(sys_get_temp_dir(),'metis-import-output-');$error=tempnam(sys_get_temp_dir(),'metis-import-error-');
register_shutdown_function(function()use($input,$output,$error){foreach([$input,$output,$error]as$p)@unlink($p);});
file_put_contents($input,$fixture."\nSELECT @@SESSION.sql_safe_updates, @@SESSION.time_zone;\n");
$process=proc_open(['mysql','--socket='.$socket,'-uroot','--batch','--skip-column-names',"--init-command=SET SESSION sql_safe_updates=1, time_zone='+05:30'"],[0=>['file',$input,'r'],1=>['file',$output,'w'],2=>['file',$error,'w']],$pipes);
$status=proc_close($process);if($status!==0)throw new RuntimeException(file_get_contents($error));
check($status===0,'complete generated script executes with MySQL delimiters and Safe Updates enabled');
$rows=array_map(fn($line)=>explode("\t",$line),file($output,FILE_IGNORE_NEW_LINES));$settings=array_pop($rows);
check($settings===['1','+05:30'],'successful script restores Safe Updates and original time zone');
check(count($rows)===108,'all 108 complete verification queries execute');
foreach($rows as$row)if(count($row)!==4 || $row[1]!==$row[2] || (int)$row[3]!==0)throw new RuntimeException('Verification mismatch');
check($c->table('users')->count()===0 && $c->table('migrations')->count()===125,'successful replacement deletes old data but preserves current migration history');
check(count($c->getSchemaBuilder()->getTableListing($testDatabase,false))===119,'successful replacement preserves all 119 table definitions');
echo "Workbench import SQL checks complete (synthetic source only).\n";
