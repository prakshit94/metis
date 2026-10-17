<?php
// All record/file mutations are confined to a temporary empty database and disk.
declare(strict_types=1);
require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Models\SystemFile;
use App\Models\SystemSetting;
use App\Modules\Core\Controllers\FileManagerController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
function checkFiles(bool $condition, string $message): void { if (!$condition) throw new RuntimeException($message); }
function fileRequest(array $data): Request { return Request::create('/', 'POST', $data); }
$source = DB::connection();
$pdo = $source->getPdo();
$sourceName = str_replace('`','``',$source->getDatabaseName());
$database = 'metis_files_test_'.bin2hex(random_bytes(6));
$root = sys_get_temp_dir().'/'.$database;
$archives = [];
$pdo->exec("CREATE DATABASE `{$database}`");
try {
    foreach (['system_files','system_settings'] as $table) $pdo->exec("CREATE TABLE `{$database}`.`{$table}` LIKE `{$sourceName}`.`{$table}`");
    $config = $source->getConfig();$config['database']=$database;unset($config['url'],$config['name']);
    config(['database.connections.files_fixture'=>$config,'database.default'=>'files_fixture','filesystems.disks.public'=>['driver'=>'local','root'=>$root.'/app/public','url'=>'/storage','throw'=>true], 'cache.default'=>'array']);
    $app->useStoragePath($root);
    Storage::forgetDisk('public');
    Illuminate\Database\Eloquent\Model::unsetEventDispatcher();
    checkFiles(DB::connection()->getDatabaseName()===$database,'Database isolation failed');
    $disk=Storage::disk('public');
    $disk->put('uploads/first image.png','fixture-one');
    $disk->put('uploads/second.png','fixture-two');
    $one=SystemFile::create(['original_name'=>'same.png','filename'=>'first image.png','mime_type'=>'image/png','path'=>'uploads/first image.png','size'=>11]);
    $two=SystemFile::create(['original_name'=>'same.png','filename'=>'second.png','mime_type'=>'image/png','path'=>'uploads/second.png','size'=>11]);
    $controller=new FileManagerController;
    $preview=$controller->preview(Request::create('/api/files/preview','GET',['f'=>'first image.png']));
    checkFiles($preview->getFile()->getPathname()===$root.'/app/public/uploads/first image.png','Preview did not resolve uploaded file');
    foreach ([['invalid'], '..', '../first image.png'] as $invalid) {
        try { $controller->preview(Request::create('/api/files/preview','GET',['f'=>$invalid]));throw new RuntimeException('Invalid preview filename accepted'); }
        catch (Symfony\Component\HttpKernel\Exception\HttpException $expected) { checkFiles($expected->getStatusCode()===404,'Wrong preview failure status'); }
    }
    $controller->setDefaultImage(fileRequest(['file_id'=>(string)$one->id,'target_name'=>'logo.png']));
    $url=SystemSetting::where('key','default_image_logo_png')->value('value');
    checkFiles($url==='/api/files/preview?f=first%20image.png','Default image lost filename or encoding');
    $controller->setLoginBackground(fileRequest(['url'=>$url]));
    checkFiles(SystemSetting::where('key','login_background_image')->value('value')===$url,'Background lost filename');
    $listingResponse=$controller->index();
    checkFiles(rawurldecode($listingResponse->headers->get('X-Default-Avatar'))==='', 'Unconfigured default header should be empty');
    $controller->setDefaultImage(fileRequest(['file_id'=>(string)$one->id,'target_name'=>'default_avatar.jpeg']));
    $listingResponse=$controller->index();
    checkFiles(rawurldecode($listingResponse->headers->get('X-Default-Avatar'))===$url, 'Configured avatar sync header lost query string');
    $listing=$listingResponse->getData(true);
    $first=collect($listing)->firstWhere('id',(string)$one->id);
    $second=collect($listing)->firstWhere('id',(string)$two->id);
    checkFiles(in_array('App Logo (PNG)',$first['defaultRoles'],true),'Selected default role missing');
    checkFiles(!in_array('App Logo (PNG)',$second['defaultRoles'],true),'Default role incorrectly applied to every upload');
    checkFiles($first['sizeBytes']===11 && $first['modifiedAt']!==null,'Exact size/date missing');
    $controller->rename(fileRequest(['id'=>$one->id,'newName'=>'renamed.png']));
    checkFiles($one->fresh()->original_name==='renamed.png' && $disk->exists($one->path),'Rename changed stored file');
    // Duplicate display names must not overwrite each other inside ZIP archives.
    DB::table('system_files')->where('id',$one->id)->update(['original_name'=>'same.png']);
    $response=$controller->downloadZip(fileRequest(['ids'=>[(string)$one->id,(string)$two->id,'asset:default_avatar.jpeg']]));
    $archive=$response->getFile()->getPathname();$archives[]=$archive;
    $zip=new ZipArchive;$zip->open($archive);
    checkFiles($zip->numFiles===3,'ZIP lost selected uploads or assets');
    checkFiles($zip->getFromName('same.png')==='fixture-one' && $zip->getFromName('1_same.png')==='fixture-two','Duplicate ZIP names lost data');
    checkFiles($zip->locateName('default_avatar.jpeg')!==false,'Selected asset missing from ZIP');$zip->close();
    try { $controller->setDefaultImage(fileRequest(['file_id'=>'999999','target_name'=>'logo.png']));throw new RuntimeException('Missing file accepted'); }
    catch (Illuminate\Database\Eloquent\ModelNotFoundException $expected) {}
    $document=SystemFile::create(['original_name'=>'document.pdf','filename'=>'document.pdf','mime_type'=>'application/pdf','path'=>'uploads/document.pdf','size'=>1]);
    try { $controller->setDefaultImage(fileRequest(['file_id'=>(string)$document->id,'target_name'=>'default_avatar.jpeg']));throw new RuntimeException('Non-image default accepted'); }
    catch (Symfony\Component\HttpKernel\Exception\HttpException $expected) { checkFiles($expected->getStatusCode()===422,'Wrong non-image status'); }
    $controller->delete(fileRequest(['id'=>$one->id]));
    checkFiles(!$disk->exists($one->path) && !SystemFile::find($one->id),'Deletion failed');
    checkFiles(!SystemSetting::where('key','login_background_image')->exists(),'Deleted background reference remained');
    checkFiles(!SystemSetting::where('key','default_image_logo_png')->exists(),'Deleted logo reference remained');
    checkFiles($controller->index()->headers->get('X-Default-Avatar')==='', 'Deletion did not reset avatar sync header');
    echo "PASS: isolated Files listing, default/background URLs and roles, logical rename, mixed ZIP selection, duplicate names, missing records, and deletion.\n";
} finally {
    foreach ($archives as $archive) if (is_file($archive)) unlink($archive);
    (new Illuminate\Filesystem\Filesystem)->deleteDirectory($root);
    DB::purge('files_fixture');
    $pdo->exec("DROP DATABASE `{$database}`");
}
