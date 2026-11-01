<?php
require dirname(__DIR__,2).'/vendor/autoload.php';
$app=require dirname(__DIR__,2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Services\BrandLogo;
function brandCheck(bool $ok,string $message): void { if(!$ok) throw new RuntimeException($message); echo 'PASS: '.$message.PHP_EOL; }
$fallback=BrandLogo::resolve(null)['src'];
brandCheck(str_starts_with($fallback,'/assets/images/logo-small.webp?v='),'fallback uses current-origin asset rather than APP_URL host/port');
$local=BrandLogo::resolve('/assets/images/farmersprofileimage.png');
brandCheck($local['src']==='/assets/images/farmersprofileimage.png?v='.filemtime(public_path('assets/images/farmersprofileimage.png')),'configured logo version tracks its own file');
brandCheck(BrandLogo::resolve('http://localhost:9000/assets/images/logo.png')['src']===$fallback,'old local asset host and port do not break the current header');
brandCheck(BrandLogo::resolve('/storage/missing-brand.png')['src']===$fallback,'missing configured local logo falls back before rendering');
brandCheck(BrandLogo::resolve('javascript:bad')['src']===$fallback && BrandLogo::resolve('/../.env')['src']===$fallback,'invalid asset paths and unsupported schemes are not rendered');
$remote='https://cdn.example.org/logo.png?signature=fixture#brand';
brandCheck(BrandLogo::resolve($remote)['src']===$remote,'external signed logo URLs remain unchanged');
$versioned=BrandLogo::resolve('/assets/images/logo.png?size=40&v=old#brand')['src'];
brandCheck($versioned==='/assets/images/logo-small.webp?size=40&v='.filemtime(public_path('assets/images/logo-small.webp')).'#brand','cache version updates without duplicating query keys or breaking fragments');
$header=file_get_contents(resource_path('views/components/header.blade.php'));
brandCheck(str_contains($header,'data-header-logo') && str_contains($header,'this.onerror=null;this.src=this.dataset.fallbackSrc;'),'header provides a non-recursive runtime fallback for failed image requests');
brandCheck(str_contains($header,'object-fit-contain rounded-2') && !str_contains($header,'aria-hidden="true" class="shadow-sm rounded-circle"'),'header logo preserves aspect ratio instead of circular cropping');
$layout=file_get_contents(resource_path('views/layouts/app.blade.php'));
brandCheck(substr_count($layout,'<link rel="icon"')===1,'layout does not advertise the same bitmap as conflicting SVG and PNG icons');
foreach(['components/header','layouts/app'] as $name){$path='/tmp/metis-'.str_replace('/','-',$name).'-branding.php';file_put_contents($path,app('blade.compiler')->compileString(file_get_contents(resource_path('views/'.$name.'.blade.php'))));passthru('php -l '.escapeshellarg($path),$status);brandCheck($status===0,$name.' compiles successfully');}

$uploads = '/tmp/metis-logo-preview-'.bin2hex(random_bytes(5));
mkdir($uploads.'/uploads', 0700, true);
copy(public_path('assets/images/logo-small.webp'), $uploads.'/uploads/selected.webp');
config(['filesystems.disks.public.root'=>$uploads, 'filesystems.disks.public.driver'=>'local']);
Illuminate\Support\Facades\Storage::forgetDisk('public');
try {
    $selected = BrandLogo::resolve('/api/files/preview?f=selected.webp')['src'];
    brandCheck(str_starts_with($selected, '/brand-logo?v='), 'File Manager preview URLs resolve to cache-versioned public branding');
    brandCheck(BrandLogo::resolve('http://localhost:9000/api/files/preview?f=selected.webp')['src']===$selected, 'legacy preview host and port do not break uploaded logos');
    foreach (['../selected.webp', 'nested/selected.webp', 'nested\\selected.webp', 'missing.webp'] as $invalid) {
        brandCheck(BrandLogo::resolve('/api/files/preview?f='.rawurlencode($invalid))['src']===$fallback, 'invalid or missing preview upload falls back safely');
    }
    brandCheck(BrandLogo::resolve('/api/files/preview?f[]=selected.webp')['src']===$fallback, 'array filename cannot bypass preview logo validation');
} finally {
    unlink($uploads.'/uploads/selected.webp');
    rmdir($uploads.'/uploads');
    rmdir($uploads);
}
