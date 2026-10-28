<?php
require dirname(__DIR__,2).'/vendor/autoload.php';
$app=require dirname(__DIR__,2).'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use App\Services\BrandLogo;
function brandCheck(bool $ok,string $message): void { if(!$ok) throw new RuntimeException($message); echo 'PASS: '.$message.PHP_EOL; }
$fallback=BrandLogo::resolve(null)['src'];
brandCheck(str_starts_with($fallback,'/assets/images/logo.png?v='),'fallback uses current-origin asset rather than APP_URL host/port');
$local=BrandLogo::resolve('/assets/images/farmersprofileimage.png');
brandCheck($local['src']==='/assets/images/farmersprofileimage.png?v='.filemtime(public_path('assets/images/farmersprofileimage.png')),'configured logo version tracks its own file');
brandCheck(BrandLogo::resolve('http://localhost:9000/assets/images/logo.png')['src']===$fallback,'old local asset host and port do not break the current header');
brandCheck(BrandLogo::resolve('/storage/missing-brand.png')['src']===$fallback,'missing configured local logo falls back before rendering');
brandCheck(BrandLogo::resolve('javascript:bad')['src']===$fallback && BrandLogo::resolve('/../.env')['src']===$fallback,'invalid asset paths and unsupported schemes are not rendered');
$remote='https://cdn.example.org/logo.png?signature=fixture#brand';
brandCheck(BrandLogo::resolve($remote)['src']===$remote,'external signed logo URLs remain unchanged');
$versioned=BrandLogo::resolve('/assets/images/logo.png?size=40&v=old#brand')['src'];
brandCheck($versioned==='/assets/images/logo.png?size=40&v='.filemtime(public_path('assets/images/logo.png')).'#brand','cache version updates without duplicating query keys or breaking fragments');
$header=file_get_contents(resource_path('views/components/header.blade.php'));
brandCheck(str_contains($header,'data-header-logo') && str_contains($header,'this.onerror=null;this.src=this.dataset.fallbackSrc;'),'header provides a non-recursive runtime fallback for failed image requests');
brandCheck(str_contains($header,'object-fit-contain rounded-2') && !str_contains($header,'aria-hidden="true" class="shadow-sm rounded-circle"'),'header logo preserves aspect ratio instead of circular cropping');
$layout=file_get_contents(resource_path('views/layouts/app.blade.php'));
brandCheck(substr_count($layout,'<link rel="icon"')===1,'layout does not advertise the same bitmap as conflicting SVG and PNG icons');
foreach(['components/header','layouts/app'] as $name){$path='/tmp/metis-'.str_replace('/','-',$name).'-branding.php';file_put_contents($path,app('blade.compiler')->compileString(file_get_contents(resource_path('views/'.$name.'.blade.php'))));passthru('php -l '.escapeshellarg($path),$status);brandCheck($status===0,$name.' compiles successfully');}
