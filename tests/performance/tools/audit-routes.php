<?php
$root = dirname(__DIR__, 3);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$rows=[];
foreach (Illuminate\Support\Facades\Route::getRoutes() as $route) {
    $action=$route->getActionName();
    $valid=true;
    if (str_contains($action,'@')) {
        [$class,$method]=explode('@',$action,2);
        $valid=method_exists($class,$method) && (new ReflectionMethod($class,$method))->isPublic();
    }
    $shadowed = [];
    if (! str_contains($route->uri(), '{')) {
        foreach (array_diff($route->methods(), ['HEAD', 'OPTIONS']) as $method) {
            $matched = Illuminate\Support\Facades\Route::getRoutes()->match(Illuminate\Http\Request::create('http://localhost/'.ltrim($route->uri(), '/'), $method));
            if ($matched->getActionName() !== $action) {
                $shadowed[$method] = $matched->getActionName();
            }
        }
    }
    $rows[]=['shadowed_by' => $shadowed, 'methods'=>$route->methods(),'uri'=>$route->uri(),'action'=>$action,'callable'=>$valid,'middleware'=>$route->gatherMiddleware()];
}
file_put_contents($root.'/tests/performance/results/'.date('Y-m-d').'-route-audit.json',json_encode($rows,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
foreach ($rows as $row) if (!$row['callable'] || $row['shadowed_by']) echo json_encode($row).PHP_EOL;
echo count($rows).' routes inspected'.PHP_EOL;
