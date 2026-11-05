<?php

use App\Modules\Core\Controllers\PageController;
use App\Modules\Users\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

require dirname(__DIR__,2).'/vendor/autoload.php';
$app=require dirname(__DIR__,2).'/bootstrap/app.php';$app->make(Kernel::class)->bootstrap();
$user=new class extends User {
    public function hasRole($roles, ?string $guard=null): bool { return true; }
    public function can($abilities, $arguments=[]): bool { return true; }
};
$user->forceFill(['id'=>999,'name'=>'Date-boundary test']);auth()->setUser($user);
$controller=app(PageController::class);
foreach ([['2026-11-05','2026-12-04',true],['2026-11-05','2026-12-05',false],['2026-01-31','2026-02-27',true],['2026-01-31','2026-02-28',false],['2028-01-31','2028-02-28',true],['2028-01-31','2028-02-29',false],['2026-12-01','2026-12-31',true],['2026-11-05','2026-11-05',true]] as [$from,$to,$valid]) {
    $request=Request::create('/admin','GET',['filter'=>'custom','from'=>$from,'to'=>$to],[],[],['HTTP_ACCEPT'=>'application/json']);
    $request->setUserResolver(fn()=>$user);
    try {
        // Exercise the real controller; pretend mode executes no report queries or business writes.
        DB::pretend(fn()=>$controller->dashboard($request));
        if (!$valid) throw new RuntimeException("Accepted overlong range $from to $to");
    } catch (ValidationException $error) { if ($valid) throw $error; }
    echo "PASS: custom range $from to $to ".($valid?'accepted':'rejected').PHP_EOL;
}
